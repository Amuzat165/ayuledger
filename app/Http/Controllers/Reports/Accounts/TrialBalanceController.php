<?php

namespace App\Http\Controllers\Reports\Accounts;

use App\BankCash;
use App\Branch;
use App\Transaction;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;
use App\Exports\TrialBalance\BranchWise;
use App\Http\Controllers\CrVoucherController;
use App\IncomeExpenseHead;

class TrialBalanceController extends Controller
{
    public function index()
    {
        $crvoucher = new CrVoucherController();
        return view('admin.accounts-report.trial-balance.index', $crvoucher->__getBranchBankCashIncomeExpenseHead());
    }

    public function branch_wise(Request $request)
    {
        $validated = $request->validate([
            'from' => 'nullable|date_format:d/m/Y|required_with:to',
            'to' => 'nullable|date_format:d/m/Y|required_with:from',
        ]);

        $from = isset($validated['from']) ? $this->normalizeDate($validated['from']) : null;
        $to = isset($validated['to']) ? $this->normalizeDate($validated['to']) : null;

        if ($from && $to && $from > $to) {
            throw ValidationException::withMessages([
                'to' => 'The end date must be on or after the start date.',
            ]);
        }

        $selectedBranch = $request->filled('branch_id') ? Branch::find($request->branch_id) : null;
        $branchScope = $request->filled('branch_id')
            ? ($selectedBranch ? $selectedBranch->name : 'Branch ' . $request->branch_id)
            : __('financial.labels.all_branches');

        $now = new \DateTime();
        $date = $now->format(Config('settings.date_format') . ' h:i:s');
        $moduleName = 'Trial Balance Report - ' . $branchScope;
        $extra = array(
            'current_date_time' => $date,
            'module_name' => $moduleName,
            'voucher_type' => 'TRIAL BALANCE REPORT - ' . $branchScope
        );
        
        $transaction_query = Transaction::query();
        if ($request->branch_id) {
            $transaction_query->where('branch_id', $request->branch_id);
        }
        if ($from && $to) {
            $transaction_query->whereBetween('voucher_date', [$from, $to]);
        }
        $transaction_query->with('Branch', 'IncomeExpenseHead', 'BankCash');
        $transactions = $transaction_query->orderBy('voucher_date', 'DESC')->get();
        $branches = $transactions->groupBy('Branch.name');
        $bank_cashes = $transactions->whereNotNull('bank_cash_id')->groupBy('BankCash.name');
        $search_by = array(
            'from' => $from,
            'to' => $to,
        );
        $items['branches'] = $branches;
        $items['bank_cashes'] = $bank_cashes;
        if ($branches->count() < 1 && $bank_cashes->count() < 1) {
            Session::flash('error', 'There Has No Transaction');
            return redirect()->back();
        }
        // Show Action
        if ($request->action == 'Show') {
            return view('admin.accounts-report.trial-balance.branch-wise.index')
                ->with('items', $items)
                ->with('extra', $extra)
                ->with('search_by', $search_by);
        }

        // Pdf Action
        if ($request->action == 'Pdf') {
            $pdf = PDF::loadView('admin.accounts-report.trial-balance.branch-wise.pdf', [
                'items' => $items,
                'extra' => $extra,
                'search_by' => $search_by,
            ])->setPaper('a4', 'landscape');
            //return $pdf->stream(date(config('settings.date_format'), strtotime($extra['current_date_time'])) . '_' . $extra['module_name'] . '.pdf');
            return $pdf->download($extra['current_date_time'] . '_' . $extra['module_name'] . '.pdf');
        }
        // Excel Action
        if ($request->action == 'Excel') {
            $BranchWise = new BranchWise([
                'items' => $items,
                'extra' => $extra,
                'search_by' => $search_by,
            ]);
            return Excel::download($BranchWise, $extra['current_date_time'] . '_' . $extra['module_name'] . '.xlsx');
        }
    }

    private function normalizeDate($value)
    {
        $date = \DateTime::createFromFormat('!d/m/Y', $value);

        return $date && $date->format('d/m/Y') === $value ? $date->format('Y-m-d') : null;
    }
}
