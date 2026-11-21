<?php

namespace App\Services;

use App\Models\VendorInvoice;
use App\Models\VendorInvoiceItem;
use App\Models\VendorPayments;
use App\Support\VendorPaymentSettlementCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Settles vendor payments against the invoice's immutable WHT snapshot. */
class VendorPaymentSettlementService
{
    private $calculator;

    public function __construct(VendorPaymentSettlementCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    public function describeInvoice(VendorInvoice $invoice): array
    {
        $payments = VendorPayments::where('invoice_id', $invoice->id)->get();
        if ($payments->count() > 1) {
            return $this->reviewSummary($invoice, $payments->first(), 'Multiple active payment summary rows exist for this invoice. Review is required.');
        }

        try {
            return $this->stateFor($invoice, $payments->first());
        } catch (ValidationException $exception) {
            return $this->reviewSummary($invoice, $payments->first(), collect($exception->errors())->flatten()->first());
        }
    }

    public function settleInvoice($invoiceId, $amount, $paymentModeId): array
    {
        $amount = $this->calculator->validatePaymentAmount($amount);

        return DB::transaction(function () use ($invoiceId, $amount, $paymentModeId) {
            // Locking the invoice serializes first-payment attempts even when no payment row exists yet.
            $invoice = VendorInvoice::whereKey($invoiceId)->lockForUpdate()->firstOrFail();
            $payments = VendorPayments::where('invoice_id', $invoice->id)->lockForUpdate()->get();
            if ($payments->count() > 1) {
                $this->reviewRequired('Multiple active payment summary rows exist for this invoice. Review is required.');
            }

            return $this->applyAndPersist($invoice, $payments->first(), $amount, $paymentModeId);
        }, 3);
    }

    public function settlePayment($paymentId, $amount, $paymentModeId): array
    {
        $amount = $this->calculator->validatePaymentAmount($amount);

        // This initial lookup only discovers the parent invoice. Both records are re-read and locked below.
        $paymentLink = VendorPayments::whereKey($paymentId)->firstOrFail();
        $invoiceId = $paymentLink->invoice_id;

        return DB::transaction(function () use ($invoiceId, $paymentId, $amount, $paymentModeId) {
            $invoice = VendorInvoice::whereKey($invoiceId)->lockForUpdate()->firstOrFail();
            $payment = VendorPayments::whereKey($paymentId)->lockForUpdate()->firstOrFail();
            if ((int) $payment->invoice_id !== (int) $invoice->id || (int) $payment->vendor_id !== (int) $invoice->vendor_id) {
                $this->reviewRequired('The payment is not consistently linked to this vendor invoice. Review is required.');
            }

            $payments = VendorPayments::where('invoice_id', $invoice->id)->lockForUpdate()->get();
            if ($payments->count() !== 1 || (int) $payments->first()->id !== (int) $payment->id) {
                $this->reviewRequired('The active payment summary is inconsistent. Review is required before payment.');
            }

            return $this->applyAndPersist($invoice, $payment, $amount, $paymentModeId);
        }, 3);
    }

    private function applyAndPersist(VendorInvoice $invoice, ?VendorPayments $payment, string $amount, $paymentModeId): array
    {
        $state = $this->stateFor($invoice, $payment);
        $result = $this->calculator->applyPayment($state, $amount);

        if (!$payment) {
            $payment = new VendorPayments();
            $payment->vendor_id = $invoice->vendor_id;
            $payment->invoice_id = $invoice->id;
        }

        // total_amount represents the amount payable to the vendor for new snapshot-backed settlements.
        $payment->total_amount = $result['net_payable'];
        $payment->amount_paid = $result['amount_paid'];
        $payment->balance_due = $result['balance_due'];
        $payment->payment_mode_id = $paymentModeId;
        $payment->save();

        $result['payment'] = $payment;
        return $result;
    }

    private function stateFor(VendorInvoice $invoice, ?VendorPayments $payment): array
    {
        $invoiceTotal = VendorInvoiceItem::where('invoice_id', $invoice->id)->sum('amount');

        return $this->calculator->state(
            $invoiceTotal,
            $invoice->withholding_tax_amount,
            $invoice->wht_enabled_snapshot,
            $invoice->wht_mode_snapshot,
            $invoice->wht_balance_due_snapshot,
            $payment ? $payment->amount_paid : '0.00',
            $payment ? $payment->total_amount : null,
            $payment ? $payment->balance_due : null
        );
    }

    private function reviewSummary(VendorInvoice $invoice, ?VendorPayments $payment, ?string $message): array
    {
        return [
            'invoice_total' => $this->formatDatabaseAmount(VendorInvoiceItem::where('invoice_id', $invoice->id)->sum('amount')),
            'wht_amount' => null,
            'net_payable' => null,
            'amount_paid' => $payment ? $payment->amount_paid : null,
            'balance_due' => null,
            'settlement_available' => false,
            'review_message' => $message ?: 'Review is required before settling this invoice.',
        ];
    }

    private function reviewRequired(string $message): void
    {
        throw ValidationException::withMessages(['invoice_id' => [$message]]);
    }

    private function formatDatabaseAmount($amount): string
    {
        $amount = (string) $amount;
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $amount)) {
            return '0.00';
        }

        $parts = explode('.', $amount, 2);
        return $parts[0] . '.' . str_pad($parts[1] ?? '', 2, '0');
    }
}
