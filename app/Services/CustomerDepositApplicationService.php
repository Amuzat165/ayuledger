<?php

namespace App\Services;

use App\Models\CustomerDeposit;
use App\Models\CustomerDepositApplication;
use App\Models\CustomerInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CustomerDepositApplicationService
{
    public function apply($depositId, $invoiceId, $amount, $idempotencyKey, $userId = null)
    {
        $validator = Validator::make(['amount' => $amount], ['amount' => ['required', 'numeric', 'gt:0']]);
        if ($validator->fails()) throw ValidationException::withMessages(['amount' => ['Application amount must be greater than zero.']]);
        $cents = app(CustomerInvoiceBalanceService::class)->toCents($amount);
        if ($cents <= 0) throw ValidationException::withMessages(['amount' => ['Application amount must be greater than zero.']]);
        $ownerId = CustomerDeposit::whereKey($depositId)->value('customer_id');
        if (!$ownerId) throw ValidationException::withMessages(['deposit' => ['Customer deposit was not found.']]);
        $fingerprint = hash('sha256', implode('|', [$ownerId, $depositId, $invoiceId, $cents]));
        $existing = CustomerDepositApplication::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) return $this->existingOrConflict($existing, $fingerprint);

        try {
            return DB::transaction(function () use ($depositId, $invoiceId, $cents, $idempotencyKey, $userId, $fingerprint) {
                // Every balance-changing operation locks customer before invoice/deposit.
                $ownerId = CustomerDeposit::whereKey($depositId)->value('customer_id');
                if (!$ownerId) throw ValidationException::withMessages(['deposit' => ['Customer deposit was not found.']]);
                DB::table('customers')->where('id', $ownerId)->lockForUpdate()->first();
                $invoice = CustomerInvoice::whereKey($invoiceId)->lockForUpdate()->first();
                if (!$invoice) throw ValidationException::withMessages(['invoice' => ['Invoice was not found.']]);
                $deposit = CustomerDeposit::whereKey($depositId)->lockForUpdate()->first();
                if (!$deposit || $deposit->status !== 'active') throw ValidationException::withMessages(['deposit' => ['Customer deposit is not available for application.']]);
                if ((int) $invoice->customer_id !== (int) $deposit->customer_id) throw ValidationException::withMessages(['invoice' => ['Deposit and invoice must belong to the same customer.']]);
                $existing = CustomerDepositApplication::where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
                if ($existing) return $this->existingOrConflict($existing, $fingerprint);

                $invoiceBalances = app(CustomerInvoiceBalanceService::class);
                $invoiceBalances->assertSnapshot($invoice);
                $invoiceCents = $invoiceBalances->balanceCentsFor($invoice);
                $depositBalances = app(CustomerDepositBalanceService::class);
                $depositCents = $depositBalances->balanceCentsFor($deposit);
                if ($cents > $depositCents) throw ValidationException::withMessages(['amount' => ['Application cannot exceed the available deposit balance.']]);
                if ($cents > $invoiceCents) throw ValidationException::withMessages(['amount' => ['Application cannot exceed the invoice balance due.']]);
                $now = now();
                $application = CustomerDepositApplication::create([
                    'customer_deposit_id' => $deposit->id, 'invoice_id' => $invoice->id,
                    'amount_applied' => number_format($cents / 100, 2, '.', ''),
                    'invoice_balance_after' => number_format(($invoiceCents - $cents) / 100, 2, '.', ''),
                    'deposit_balance_after' => number_format(($depositCents - $cents) / 100, 2, '.', ''),
                    'applied_at' => $now, 'status' => 'applied', 'idempotency_key' => $idempotencyKey,
                    'payload_fingerprint' => $fingerprint, 'applied_by' => $userId,
                ]);
                return $application;
            }, 3);
        } catch (\Illuminate\Database\QueryException $e) {
            $existing = CustomerDepositApplication::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) return $this->existingOrConflict($existing, $fingerprint);
            throw $e;
        }
    }

    private function existingOrConflict(CustomerDepositApplication $application, $fingerprint)
    {
        if (!hash_equals($application->payload_fingerprint, $fingerprint)) abort(409, 'Idempotency key conflict.');
        return $application;
    }
}
