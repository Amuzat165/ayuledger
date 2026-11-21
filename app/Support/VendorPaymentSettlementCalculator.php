<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/** Exact-cent calculations shared by vendor payment settlement and its tests. */
class VendorPaymentSettlementCalculator
{
    private const MAX_CENTS = 9999999999; // DECIMAL(10,2), matching the existing payment columns.

    public function state(
        $invoiceTotal,
        $withholdingAmount,
        $snapshotEnabled,
        $snapshotMode,
        $snapshotBalanceDue,
        $amountPaid = '0.00',
        $paymentTotal = null,
        $paymentBalanceDue = null
    ): array {
        if ($snapshotEnabled === null || !in_array($snapshotMode, ['manual', 'rule_based'], true)) {
            $this->reviewRequired('This historical vendor invoice has no usable WHT snapshot. Review its payable amount before recording a payment.');
        }

        $grossCents = $this->toCents($invoiceTotal, 'invoice_id');
        $whtCents = $this->toCents($withholdingAmount, 'invoice_id');
        $storedBalanceCents = $this->toCents($snapshotBalanceDue, 'invoice_id');

        if ($whtCents > $grossCents || (!$snapshotEnabled && $whtCents !== 0)) {
            $this->reviewRequired('The vendor invoice WHT snapshot is inconsistent. Review is required before payment.');
        }

        $payableCents = $grossCents - $whtCents;
        if ($storedBalanceCents !== $payableCents) {
            $this->reviewRequired('The vendor invoice total and WHT snapshot do not agree. Review is required before payment.');
        }

        $paidCents = $this->toCents($amountPaid, 'invoice_id');
        if ($paidCents > $payableCents) {
            $this->reviewRequired('Recorded vendor payments exceed the invoice net payable. Review is required before payment.');
        }

        if ($paymentTotal !== null || $paymentBalanceDue !== null) {
            if ($paymentTotal === null || $paymentBalanceDue === null
                || $this->toCents($paymentTotal, 'invoice_id') !== $payableCents
                || $this->toCents($paymentBalanceDue, 'invoice_id') !== ($payableCents - $paidCents)) {
                $this->reviewRequired('The existing vendor payment balance does not match the invoice snapshot. Review is required before payment.');
            }
        }

        return [
            'invoice_total' => $this->fromCents($grossCents),
            'wht_amount' => $this->fromCents($whtCents),
            'net_payable' => $this->fromCents($payableCents),
            'amount_paid' => $this->fromCents($paidCents),
            'balance_due' => $this->fromCents($payableCents - $paidCents),
            'settlement_available' => true,
        ];
    }

    public function applyPayment(array $state, $requestedAmount): array
    {
        $amountCents = $this->toCents($requestedAmount, 'amount');
        if ($amountCents <= 0) {
            throw ValidationException::withMessages(['amount' => ['Payment amount must be greater than zero.']]);
        }

        $balanceCents = $this->toCents($state['balance_due'], 'amount');
        if ($amountCents > $balanceCents) {
            throw ValidationException::withMessages(['amount' => ['Payment amount exceeds the outstanding vendor payable. Overpayment is not allowed.']]);
        }

        $paidCents = $this->toCents($state['amount_paid'], 'amount') + $amountCents;
        return array_merge($state, [
            'new_payment_amount' => $this->fromCents($amountCents),
            'amount_paid' => $this->fromCents($paidCents),
            'balance_due' => $this->fromCents($balanceCents - $amountCents),
        ]);
    }

    public function validatePaymentAmount($amount): string
    {
        $cents = $this->toCents($amount, 'amount');
        if ($cents <= 0) {
            throw ValidationException::withMessages(['amount' => ['Payment amount must be greater than zero.']]);
        }

        return $this->fromCents($cents);
    }

    private function toCents($amount, string $field): int
    {
        $amount = (string) $amount;
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $amount)) {
            throw ValidationException::withMessages([$field => ['Enter a valid non-negative amount with no more than two decimal places.']]);
        }

        $parts = explode('.', $amount, 2);
        $whole = ltrim($parts[0], '0');
        $whole = $whole === '' ? '0' : $whole;
        if (strlen($whole) > 8) {
            throw ValidationException::withMessages([$field => ['The amount exceeds the supported monetary range.']]);
        }

        $cents = ((int) $whole * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
        if ($cents > self::MAX_CENTS) {
            throw ValidationException::withMessages([$field => ['The amount exceeds the supported monetary range.']]);
        }

        return $cents;
    }

    private function fromCents(int $cents): string
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function reviewRequired(string $message): void
    {
        throw ValidationException::withMessages(['invoice_id' => [$message]]);
    }
}
