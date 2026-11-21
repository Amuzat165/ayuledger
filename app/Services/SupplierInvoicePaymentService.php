<?php

namespace App\Services;

use App\Models\SupplierInvoice;
use App\Models\SupplierInvoicePayment;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierInvoicePaymentService
{
    public function pay(int $invoiceId, $amount, string $paymentDate, int $paymentModeId, string $submissionToken, ?int $userId): SupplierInvoicePayment
    {
        $cents = $this->toCents($amount);
        if ($cents <= 0) {
            throw ValidationException::withMessages(['payment_amount' => [__('supplier_payments.errors.positive_amount')]]);
        }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $paymentDate, $dateParts)
            || !checkdate((int) $dateParts[2], (int) $dateParts[3], (int) $dateParts[1])) {
            throw ValidationException::withMessages(['payment_date' => [__('supplier_payments.errors.invalid_date')]]);
        }

        $fingerprint = hash('sha256', implode('|', [$invoiceId, $cents, $paymentDate, $paymentModeId]));
        $existing = SupplierInvoicePayment::where('submission_token', $submissionToken)->first();
        if ($existing) return $this->existingOrConflict($existing, $fingerprint);

        try {
            return DB::transaction(function () use ($invoiceId, $cents, $amount, $paymentDate, $paymentModeId, $submissionToken, $userId, $fingerprint) {
                $invoice = SupplierInvoice::whereKey($invoiceId)->lockForUpdate()->firstOrFail();
                $existing = SupplierInvoicePayment::where('submission_token', $submissionToken)->lockForUpdate()->first();
                if ($existing) return $this->existingOrConflict($existing, $fingerprint);

                if (!$invoice->isPayable() || $invoice->amount_remaining === null) {
                    throw ValidationException::withMessages(['invoice' => [__('supplier_payments.errors.invoice_not_payable')]]);
                }
                $remainingCents = $this->toCents($invoice->amount_remaining);
                if ($remainingCents <= 0) {
                    throw ValidationException::withMessages(['invoice' => [__('supplier_payments.errors.no_balance')]]);
                }
                if ($cents > $remainingCents) {
                    throw ValidationException::withMessages(['payment_amount' => [__('supplier_payments.errors.exceeds_balance')]]);
                }

                $mode = DB::table('bank_cashes')->where('id', $paymentModeId)->whereNull('deleted_at')->first();
                if (!$mode) {
                    throw ValidationException::withMessages(['payment_mode_id' => [__('supplier_payments.errors.invalid_method')]]);
                }
                $sequence = DB::table('supplier_invoice_payment_sequences')->where('id', 1)->lockForUpdate()->first();
                if (!$sequence) throw new \RuntimeException('Supplier payment sequence is not initialized.');
                $nextNumber = (int) $sequence->last_number + 1;
                DB::table('supplier_invoice_payment_sequences')->where('id', 1)->update(['last_number' => $nextNumber]);

                $newRemainingCents = $remainingCents - $cents;
                $paidCents = $this->toCents($invoice->amount_paid ?: '0.00') + $cents;
                $payment = SupplierInvoicePayment::create([
                    'supplier_invoice_id' => $invoice->id,
                    'payment_reference' => 'SIP-' . str_pad((string) $nextNumber, 8, '0', STR_PAD_LEFT),
                    'payment_date' => $paymentDate,
                    'payment_amount' => $this->fromCents($cents),
                    'payment_mode_id' => $mode->id,
                    'payment_mode_name' => $mode->name,
                    'currency_code' => $invoice->currency_code,
                    'currency_symbol' => $invoice->currency_symbol,
                    'currency_position' => $invoice->currency_position,
                    'previous_amount_remaining' => $this->fromCents($remainingCents),
                    'new_amount_remaining' => $this->fromCents($newRemainingCents),
                    'submission_token' => $submissionToken,
                    'payload_fingerprint' => $fingerprint,
                    'created_by' => $userId,
                ]);

                $invoice->amount_paid = $this->fromCents($paidCents);
                $invoice->amount_remaining = $this->fromCents($newRemainingCents);
                $invoice->save();

                return $payment->load(['supplierInvoice.vendor', 'supplierInvoice.purchaseOrder', 'paymentMode', 'creator']);
            }, 3);
        } catch (QueryException $exception) {
            // Unique token protects retries that race; return only an identical payload.
            $existing = SupplierInvoicePayment::where('submission_token', $submissionToken)->first();
            if ($existing) return $this->existingOrConflict($existing, $fingerprint);
            throw $exception;
        }
    }

    private function existingOrConflict(SupplierInvoicePayment $payment, string $fingerprint): SupplierInvoicePayment
    {
        if (!hash_equals($payment->payload_fingerprint, $fingerprint)) abort(409, __('supplier_payments.errors.submission_conflict'));
        return $payment->load(['supplierInvoice.vendor', 'supplierInvoice.purchaseOrder', 'paymentMode', 'creator']);
    }

    private function toCents($amount): int
    {
        $value = (string) $amount;
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $value)) {
            throw ValidationException::withMessages(['payment_amount' => [__('supplier_payments.errors.invalid_amount')]]);
        }
        $parts = explode('.', $value, 2);
        return ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
    }

    private function fromCents(int $cents): string
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
