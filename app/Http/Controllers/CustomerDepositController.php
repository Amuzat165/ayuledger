<?php

namespace App\Http\Controllers;

use App\Models\CustomerDeposit;
use App\Services\CustomerDepositApplicationService;
use App\Services\CustomerDepositBalanceService;
use App\Services\CustomerInvoiceBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerDepositController extends Controller
{
    public function index()
    {
        $deposits = CustomerDeposit::with(['customer', 'sourceReceipt', 'sourceInvoice', 'applications.invoice', 'applications.operator'])->latest()->get();
        foreach ($deposits as $deposit) $deposit->available_balance = app(CustomerDepositBalanceService::class)->balanceFor($deposit);
        return view('pages.customers.deposit-index', [
            'title' => 'Customer Deposits', 'deposits' => $deposits,
            'currency' => config('settings.currency_symbol', config('settings.currency_code', '')),
        ]);
    }

    public function applyForm($depositId)
    {
        $deposit = CustomerDeposit::with('customer')->findOrFail($depositId);
        $balance = app(CustomerDepositBalanceService::class)->balanceFor($deposit);
        $invoices = $deposit->customer->invoices()->get()->filter(function ($invoice) {
            try { return app(CustomerInvoiceBalanceService::class)->balanceCentsFor($invoice) > 0; }
            catch (ValidationException $e) { return false; }
        });
        return view('pages.customers.deposit-apply', [
            'title' => 'Apply Customer Deposit', 'deposit' => $deposit, 'balance' => $balance,
            'invoices' => $invoices, 'idempotencyKey' => (string) Str::uuid(),
            'currency' => config('settings.currency_symbol', config('settings.currency_code', '')),
        ]);
    }

    public function apply(Request $request, $depositId)
    {
        $data = $request->validate([
            'invoice_id' => ['required', 'integer'], 'amount' => ['required', 'numeric', 'gt:0'],
            'idempotency_key' => ['required', 'uuid'],
        ]);
        app(CustomerDepositApplicationService::class)->apply($depositId, $data['invoice_id'], $data['amount'], $data['idempotency_key'], $request->user()->id);
        return redirect()->route('customer-deposits.index')->with('success', 'Customer deposit applied successfully.');
    }
}
