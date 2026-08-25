<?php

namespace App\Services;

use App\Models\CustomerInvoiceItem;
use App\Models\CustomerPayments;
use App\Models\VendorInvoiceItem;
use App\Models\VendorPayments;
use App\Models\Expenses;
use Illuminate\Support\Collection;

class GeneralLedgerReportService
{
    public function transactions($from, $to, $branchId = null)
    {
        $items = new Collection();

        $sales = CustomerInvoiceItem::with(['inventory', 'invoice.customer'])
            ->whereBetween('created_at', [$from, $to])
            ->when($branchId, function ($query) use ($branchId) {
                $query->whereHas('inventory', function ($inventory) use ($branchId) {
                    $inventory->where('branch_id', $branchId);
                });
            })
            ->get();

        foreach ($sales as $sale) {
            $description = 'Sales of ' . ($sale->inventory->description ?? 'Inventory');
            $items->push($this->row('1010', 'Sales Income', $sale->created_at, 'INV-' . $sale->id, 'Invoice', $description, 0, $sale->amount, 'Customer'));
            $items->push($this->row('1011', 'Account Receivable', $sale->created_at, 'INV-' . $sale->id, 'Invoice', $description, $sale->amount, 0, 'Customer'));
        }

        $customerPayments = CustomerPayments::with(['customer', 'invoice.items.inventory', 'paymentMode'])
            ->whereBetween('created_at', [$from, $to])
            ->when($branchId, function ($query) use ($branchId) {
                $query->whereHas('invoice.items.inventory', function ($inventory) use ($branchId) {
                    $inventory->where('branch_id', $branchId);
                });
            })
            ->get();

        foreach ($customerPayments as $payment) {
            $paymentMode = $payment->paymentMode->name ?? 'Payment';
            $description = 'Receipt from ' . ($payment->customer->name ?? 'Customer');
            $items->push($this->row('1010', $paymentMode, $payment->created_at, 'REC-' . $payment->id, 'Receipt', $description, $payment->amount_paid, 0, 'Customer', $paymentMode));
            $items->push($this->row('1011', 'Account Receivable', $payment->created_at, 'REC-' . $payment->id, 'Receipt', $description, 0, $payment->amount_paid, 'Customer', $paymentMode));
        }

        $purchases = VendorInvoiceItem::with(['inventory', 'invoice.vendor'])
            ->whereBetween('created_at', [$from, $to])
            ->when($branchId, function ($query) use ($branchId) {
                $query->whereHas('inventory', function ($inventory) use ($branchId) {
                    $inventory->where('branch_id', $branchId);
                });
            })
            ->get();

        foreach ($purchases as $purchase) {
            $description = 'Purchase of Goods from ' . ($purchase->invoice->vendor->name ?? 'Vendor');
            $items->push($this->row('2000', 'Purchase Inventory', $purchase->created_at, 'PO-' . $purchase->id, 'Purchase Order', $description, $purchase->amount, 0, 'Vendor'));
            $items->push($this->row('2001', 'Account Payable', $purchase->created_at, 'PO-' . $purchase->id, 'Purchase Order', $description, 0, $purchase->amount, 'Vendor'));
        }

        $vendorPayments = VendorPayments::with(['vendor', 'invoice.items.inventory', 'paymentMode'])
            ->whereBetween('created_at', [$from, $to])
            ->when($branchId, function ($query) use ($branchId) {
                $query->whereHas('invoice.items.inventory', function ($inventory) use ($branchId) {
                    $inventory->where('branch_id', $branchId);
                });
            })
            ->get();

        foreach ($vendorPayments as $payment) {
            $paymentMode = $payment->paymentMode->name ?? 'Payment';
            $description = 'Payment to ' . ($payment->vendor->name ?? 'Vendor');
            $items->push($this->row('2000', $paymentMode, $payment->created_at, 'PAY-' . $payment->id, 'Payment', $description, 0, $payment->amount_paid, 'Vendor', $paymentMode));
            $items->push($this->row('2001', 'Account Payable', $payment->created_at, 'PAY-' . $payment->id, 'Payment', $description, $payment->amount_paid, 0, 'Vendor', $paymentMode));
        }

        $expenses = Expenses::query()
            ->whereBetween('expense_date', [$from, $to])
            ->when($branchId, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->get();

        foreach ($expenses as $expense) {
            $items->push($this->row('6000', $expense->description, $expense->expense_date, 'EXP-' . $expense->id, 'Expense', $expense->description, $expense->amount, 0, 'Expense'));
            $items->push($this->row('6001', 'Cash', $expense->expense_date, 'EXP-' . $expense->id, 'Expense', $expense->description, 0, $expense->amount, 'Expense', 'Cash'));
        }

        $items = $items->sortBy(function ($item) {
            return $item->trans_date . '-' . $item->reference . '-' . $item->account_id;
        })->values();

        $balances = [];
        return $items->map(function ($item) use (&$balances) {
            $key = $item->account_id . '-' . $item->transaction_type;
            $balances[$key] = ($balances[$key] ?? 0) + $item->debit - $item->credit;
            $item->balance = $balances[$key];
            return $item;
        });
    }

    private function row($accountId, $accountDescription, $date, $reference, $journal, $description, $debit, $credit, $transactionType, $paymentMode = null)
    {
        return (object) [
            'account_id' => $accountId,
            'account_description' => $accountDescription,
            'trans_date' => $date,
            'reference' => $reference,
            'journal' => $journal,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'balance' => 0,
            'transaction_type' => $transactionType,
            'payment_mode' => $paymentMode,
        ];
    }
}
