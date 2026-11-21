<?php

namespace App\Services;

use App\Models\CustomerDeposit;
use Illuminate\Support\Facades\DB;

class CustomerDepositBalanceService
{
    public function balanceCentsFor(CustomerDeposit $deposit)
    {
        $applied = DB::table('customer_deposit_applications')->where('customer_deposit_id', $deposit->id)->where('status', 'applied')->sum('amount_applied');
        return max(0, $this->toCents($deposit->original_amount) - $this->toCents($applied ?: '0.00'));
    }

    public function balanceFor(CustomerDeposit $deposit)
    {
        return number_format($this->balanceCentsFor($deposit) / 100, 2, '.', '');
    }

    public function availableForCustomer($customerId)
    {
        $rows = DB::table('customer_deposits')->where('customer_id', $customerId)->where('status', 'active')->get();
        $total = 0;
        foreach ($rows as $row) $total += $this->balanceCentsFor(new CustomerDeposit((array) $row));
        return number_format($total / 100, 2, '.', '');
    }

    private function toCents($amount)
    {
        $parts = explode('.', (string) $amount, 2);
        return ((int) ($parts[0] ?? 0) * 100) + (int) str_pad($parts[1] ?? '0', 2, '0');
    }
}
