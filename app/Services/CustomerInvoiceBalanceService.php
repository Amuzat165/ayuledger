<?php

namespace App\Services;

use App\Models\CustomerInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerInvoiceBalanceService
{
    public function balanceFor(CustomerInvoice $invoice)
    {
        return number_format($this->balanceCentsFor($invoice) / 100, 2, '.', '');
    }

    public function balanceCentsFor(CustomerInvoice $invoice)
    {
        $this->assertSnapshot($invoice);
        $paid = DB::table('customer_payment_allocations as a')
            ->join('customer_payment_receipts as r', 'r.id', '=', 'a.receipt_id')
            ->where('a.invoice_id', $invoice->id)->where('a.status', 'allocated')->where('r.status', 'received')
            ->sum('a.allocated_amount');
        $deposits = DB::table('customer_deposit_applications')->where('invoice_id', $invoice->id)->where('status', 'applied')->sum('amount_applied');
        $invoiceBalance = (int) $invoice->financial_snapshot_version >= 2
            ? $this->toCents($invoice->balance_due)
            : $this->toCents($invoice->total_due);

        return max(0, $invoiceBalance - $this->toCents($paid ?: '0.00') - $this->toCents($deposits ?: '0.00'));
    }

    public function assertSnapshot(CustomerInvoice $invoice)
    {
        $version = (int) $invoice->financial_snapshot_version;
        if (!in_array($version, [1, 2], true) || $invoice->subtotal === null || $invoice->vat_percentage === null || $invoice->vat_amount === null || $invoice->total_due === null) {
            throw ValidationException::withMessages(['invoice' => ['Historical invoice requires review before payment can be recorded.']]);
        }
        $subtotal = $this->toCents($invoice->subtotal);
        $vat = $this->toCents($invoice->vat_amount);
        $total = $this->toCents($invoice->total_due);
        $expectedVat = (int) round(((float) $invoice->subtotal * (float) $invoice->vat_percentage / 100) * 100);
        if ($total !== $subtotal + $vat || $vat !== $expectedVat) {
            throw ValidationException::withMessages(['invoice' => ['Historical invoice requires review before payment can be recorded.']]);
        }

        if ($version === 2) {
            if ($invoice->balance_due === null) {
                throw ValidationException::withMessages(['invoice' => ['Invoice discount snapshot requires review before payment can be recorded.']]);
            }

            $balance = $this->toCents($invoice->balance_due);
            if ($invoice->discount_percentage === null && $invoice->discount_amount === null) {
                if ($balance !== $total) {
                    throw ValidationException::withMessages(['invoice' => ['Invoice discount snapshot requires review before payment can be recorded.']]);
                }
                return;
            }

            if ($invoice->discount_percentage === null || $invoice->discount_amount === null
                || !is_numeric($invoice->discount_percentage) || !is_finite((float) $invoice->discount_percentage)
                || !preg_match('/^\d+(?:\.\d{1,2})?$/D', (string) $invoice->discount_percentage)
                || (float) $invoice->discount_percentage < 0 || (float) $invoice->discount_percentage > 100) {
                throw ValidationException::withMessages(['invoice' => ['Invoice discount snapshot requires review before payment can be recorded.']]);
            }

            $discount = $this->toCents($invoice->discount_amount);
            $expectedDiscount = (int) round($total * (float) $invoice->discount_percentage / 100);
            if ($discount !== $expectedDiscount || $balance !== $total - $discount) {
                throw ValidationException::withMessages(['invoice' => ['Invoice discount snapshot requires review before payment can be recorded.']]);
            }
        }
    }

    public function toCents($amount)
    {
        if (!is_numeric($amount) || !preg_match('/^\d+(?:\.\d{1,2})?$/', (string) $amount)) {
            throw ValidationException::withMessages(['amount' => ['Enter a valid amount with no more than two decimal places.']]);
        }
        $parts = explode('.', (string) $amount, 2);
        return ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '0', 2, '0');
    }
}
