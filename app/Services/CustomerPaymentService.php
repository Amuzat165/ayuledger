<?php

namespace App\Services;

use App\Models\CustomerInvoice;
use App\Models\CustomerPaymentAllocation;
use App\Models\CustomerPaymentReceipt;
use App\Models\CustomerDeposit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerPaymentService
{
    public function pay($invoiceId, $customerId, $amount, $paymentModeId, $idempotencyKey, $userId = null)
    {
        $cents = $this->toCents($amount);
        $fingerprint = hash('sha256', implode('|', [$customerId, $invoiceId, $cents, $paymentModeId]));

        $existing = CustomerPaymentReceipt::where('idempotency_key', $idempotencyKey)->with(['allocations', 'deposit'])->first();
        if ($existing) return $this->existingOrConflict($existing, $fingerprint);

        try {
            return DB::transaction(function () use ($invoiceId, $customerId, $cents, $paymentModeId, $idempotencyKey, $userId, $fingerprint) {
                // Locking customer before invoice keeps the order consistent for this payment workflow.
                DB::table('customers')->where('id', $customerId)->lockForUpdate()->first();
                $invoice = CustomerInvoice::where('customer_id', $customerId)->whereKey($invoiceId)->lockForUpdate()->first();
                if (!$invoice) throw ValidationException::withMessages(['invoice' => ['Invoice was not found for this customer.']]);
                $balances = app(CustomerInvoiceBalanceService::class);
                $balances->assertSnapshot($invoice);

                $existing = CustomerPaymentReceipt::where('idempotency_key', $idempotencyKey)->with(['allocations', 'deposit'])->lockForUpdate()->first();
                if ($existing) return $this->existingOrConflict($existing, $fingerprint);

                $balanceCents = $balances->balanceCentsFor($invoice);
                if ($balanceCents <= 0) throw ValidationException::withMessages(['amount' => ['This invoice has no balance due.']]);
                if ($cents <= 0) throw ValidationException::withMessages(['amount' => ['Payment amount must be greater than zero.']]);
                $allocatedCents = min($balanceCents, $cents);
                $depositCents = $cents - $allocatedCents;

                $mode = DB::table('bank_cashes')->where('id', $paymentModeId)->first();
                if (!$mode) throw ValidationException::withMessages(['payment_mode_id' => ['Select a valid payment method.']]);
                $now = now();
                $sequence = DB::table('customer_payment_receipt_sequences')->where('id', 1)->lockForUpdate()->first();
                if (!$sequence) throw new \RuntimeException('Receipt sequence is not initialized.');
                $number = (int) $sequence->last_number + 1;
                DB::table('customer_payment_receipt_sequences')->where('id', 1)->update(['last_number' => $number]);

                $receipt = CustomerPaymentReceipt::create([
                    'receipt_number' => 'AYU-RCP-' . str_pad((string) $number, 6, '0', STR_PAD_LEFT),
                    'idempotency_key' => $idempotencyKey, 'payload_fingerprint' => $fingerprint,
                    'customer_id' => $customerId, 'amount_received' => number_format($cents / 100, 2, '.', ''),
                    'deposit_created_amount' => number_format($depositCents / 100, 2, '.', ''),
                    'payment_mode_id' => $paymentModeId, 'payment_mode_name' => $mode->name ?? null,
                    'received_at' => $now, 'status' => 'received', 'created_by' => $userId,
                ]);
                $allocation = CustomerPaymentAllocation::create([
                    'receipt_id' => $receipt->id, 'invoice_id' => $invoiceId,
                    'allocated_amount' => number_format($allocatedCents / 100, 2, '.', ''),
                    'balance_after' => number_format(($balanceCents - $allocatedCents) / 100, 2, '.', ''),
                    'allocated_at' => $now, 'status' => 'allocated', 'user_id' => $userId,
                ]);
                if ($depositCents > 0) {
                    $deposit = CustomerDeposit::create([
                        'customer_id' => $customerId, 'source_payment_receipt_id' => $receipt->id,
                        'source_invoice_id' => $invoiceId, 'original_amount' => number_format($depositCents / 100, 2, '.', ''),
                        'status' => 'active', 'created_by' => $userId,
                    ]);
                    $receipt->setRelation('deposit', $deposit);
                }
                $receipt->customer_deposit_balance_after = app(CustomerDepositBalanceService::class)->availableForCustomer($customerId);
                $receipt->save();
                $receipt->setRelation('allocations', collect([$allocation]));
                $receipt->setRelation('customer', $invoice->customer);
                $receipt->setRelation('paymentMode', $mode);
                return $receipt;
            }, 3);
        } catch (\Illuminate\Database\QueryException $e) {
            // Unique idempotency key protects retries that race across requests/connections.
            $existing = CustomerPaymentReceipt::where('idempotency_key', $idempotencyKey)->with(['allocations', 'deposit'])->first();
            if ($existing) return $this->existingOrConflict($existing, $fingerprint);
            throw $e;
        }
    }

    public function balanceFor(CustomerInvoice $invoice)
    {
        return app(CustomerInvoiceBalanceService::class)->balanceFor($invoice);
    }

    private function existingOrConflict(CustomerPaymentReceipt $receipt, $fingerprint)
    {
        if (!hash_equals($receipt->payload_fingerprint, $fingerprint)) abort(409, 'Idempotency key conflict.');
        return $receipt;
    }

    private function toCents($amount)
    {
        if (!is_numeric($amount) || !preg_match('/^\d+(?:\.\d{1,2})?$/', (string) $amount)) {
            throw ValidationException::withMessages(['amount' => ['Enter a valid amount with no more than two decimal places.']]);
        }
        $parts = explode('.', (string) $amount, 2);
        return ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '0', 2, '0');
    }
}
