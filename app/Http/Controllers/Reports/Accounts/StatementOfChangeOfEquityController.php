<?php

namespace App\Http\Controllers\Reports\Accounts;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade as PDF;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class StatementOfChangeOfEquityController extends Controller
{
    public function index()
    {
        return view('admin.accounts-report.statement-of-change-of-equity.index', Helper::__getBranchBankCashIncomeExpenseHead());
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function branch_wise(Request $request)
    {
        $request->validate([
            'end_from' => 'required',
            'end_to' => 'required',
            'start_from' => 'required',
            'start_to' => 'required',
        ]);
        $now = new \DateTime();
        $date = $now->format(Config('settings.date_format') . ' h:i:s');
        $extra = array(
            'current_date_time' => $date,
            'module_name' => 'Statement of Changes in Equity',
            'voucher_type' => 'STATEMENT OF CHANGES IN EQUITY'
        );
        
        $StatementOfEquity = $this->getStatementOfChangeOfEquity(
            $request->branch_id,
            $request->start_from,
            $request->start_to,
            $request->end_from,
            $request->end_to
        );
        
        // branches from cache
        $branches = Helper::__getBranchBankCashIncomeExpenseHead()['branches'];
        $search_by = array(
            'branch_name' => ($request->branch_id) ? $branches->where('id', $request->branch_id)->first()->name : 'All Branch',
            'start_from' => ($request->start_from) ?  date('d/m/Y', strtotime($request->start_from)) : null,
            'start_to' => ($request->start_to) ?  date('d/m/Y', strtotime($request->start_to)) : null,
            'end_from' => ($request->end_from) ?  date('d/m/Y', strtotime($request->end_from)) : null,
            'end_to' => ($request->end_to) ?  date('d/m/Y', strtotime($request->end_to)) : null,
        );
        
        // Show Action
        if ($request->action == 'Show') {
            return view('admin.accounts-report.statement-of-change-of-equity.branch-wise.index')
                ->with('particulars', $StatementOfEquity)
                ->with('extra', $extra)
                ->with('search_by', $search_by);
        }

        // Pdf Action
        if ($request->action == 'Pdf') {
            $pdf = PDF::loadView('admin.accounts-report.statement-of-change-of-equity.branch-wise.pdf', [
                'particulars' => $StatementOfEquity,
                'extra' => $extra,
                'search_by' => $search_by,
            ])->setPaper('a4', 'landscape');
            return $pdf->download($extra['current_date_time'] . '_' . $extra['module_name'] . '.pdf');
        }

        // Excel Action
        if ($request->action == 'Excel') {
            return Excel::download(new \App\Exports\StatementOfChangeOfEquity\BranchWise([
                'particulars' => $StatementOfEquity,
                'extra' => $extra,
                'search_by' => $search_by,
            ]), $extra['current_date_time'] . '_' . $extra['module_name'] . '.xlsx');
        }
    }

    /**
     * Calculate Statement of Changes in Equity
     * 
     * @param $branch_id
     * @param $start_from
     * @param $start_to
     * @param $end_from
     * @param $end_to
     * @return array
     */
    public function getStatementOfChangeOfEquity($branch_id, $start_from, $start_to, $end_from, $end_to)
    {
        // Get Balance Sheet data to extract equity components
        $balance_sheet_types = config('accounts_config.balance_sheet');
        $all_types_start = Helper::totalAmountByLedgerType($balance_sheet_types, $branch_id, $start_from, $start_to);
        $all_types_end = Helper::totalAmountByLedgerType($balance_sheet_types, $branch_id, $end_from, $end_to);

        // Get Profit/Loss
        $ProfitOrLossController = new ProfitAndLossAccountController();
        $ProfitOrLoss = $ProfitOrLossController->getProfitOrLoss(
            $branch_id,
            $start_from,
            $start_to,
            $end_from,
            $end_to
        );

        // Get Retained Earnings
        $RetainedEarningsController = new RetainedEarningsController();
        $RetainedEarnings = $RetainedEarningsController->getRetainedEarnings(
            $branch_id,
            $start_from,
            $start_to,
            $end_from,
            $end_to
        );

        // Equity Components
        // Share Capital Code 108
        $ShareCapitalBalance = [
            'start_balance' => $all_types_start['items'][108]['value'] ?? 0,
            'end_balance' => $all_types_end['items'][108]['value'] ?? 0,
        ];

        // Share Premium - Extract from balance sheet (similar to Share Capital)
        $SharePremiumBalance = [
            'start_balance' => 0,
            'end_balance' => 0,
        ];

        // Retained Earnings from previous calculation
        $RetainedEarningsBalance = $RetainedEarnings['NetProfitOrLoss'];

        // Other Reserves - Code 113 (Share Money Deposit)
        $OtherReservesBalance = [
            'start_balance' => $all_types_start['items'][113]['value'] ?? 0,
            'end_balance' => $all_types_end['items'][113]['value'] ?? 0,
        ];

        // Calculate net profit
        $NetProfitOrLossValue = $ProfitOrLoss['NetProfitOrLoss']['end_balance'] ?? 0;

        // Calculate movements
        $ShareCapitalIssued = max(0, $ShareCapitalBalance['end_balance'] - $ShareCapitalBalance['start_balance']);
        $OtherReservesMovement = $OtherReservesBalance['end_balance'] - $OtherReservesBalance['start_balance'];

        // Dividends (Type 122 - Dividend/Distributions)
        $types = [122];
        $all_types_div_start = Helper::totalAmountByLedgerType($types, $branch_id, $start_from, $start_to);
        $all_types_div_end = Helper::totalAmountByLedgerType($types, $branch_id, $end_from, $end_to);
        $DividendBalance = [
            'start_balance' => $all_types_div_start['items'][122]['value'] ?? 0,
            'end_balance' => $all_types_div_end['items'][122]['value'] ?? 0,
        ];

        // Total Equity
        $TotalEquityStart = $ShareCapitalBalance['start_balance'] + $SharePremiumBalance['start_balance'] + 
                            $RetainedEarningsBalance['start_balance'] + $OtherReservesBalance['start_balance'];
        $TotalEquityEnd = $ShareCapitalBalance['end_balance'] + $SharePremiumBalance['end_balance'] + 
                          $RetainedEarningsBalance['end_balance'] + $OtherReservesBalance['end_balance'];

        $particulars = [
            'BalanceAtBeginningOfPeriod' => [
                'name' => 'Balance at beginning of period',
                'code' => 'OB',
                'share_capital' => $ShareCapitalBalance['start_balance'],
                'share_premium' => $SharePremiumBalance['start_balance'],
                'retained_earnings' => $RetainedEarningsBalance['start_balance'],
                'other_reserves' => $OtherReservesBalance['start_balance'],
                'total' => $TotalEquityStart,
            ],
            'ProfitLossForThePeriod' => [
                'name' => 'Profit / (Loss) for the period',
                'code' => 'P/L',
                'share_capital' => 0,
                'share_premium' => 0,
                'retained_earnings' => $NetProfitOrLossValue,
                'other_reserves' => 0,
                'total' => $NetProfitOrLossValue,
            ],
            'OtherComprehensiveIncome' => [
                'name' => 'Other Comprehensive Income',
                'code' => 'OCI',
                'share_capital' => 0,
                'share_premium' => 0,
                'retained_earnings' => 0,
                'other_reserves' => 0,
                'total' => 0,
            ],
            'TotalComprehensiveIncome' => [
                'name' => 'Total Comprehensive Income',
                'code' => 'TCI',
                'share_capital' => 0,
                'share_premium' => 0,
                'retained_earnings' => $NetProfitOrLossValue,
                'other_reserves' => 0,
                'total' => $NetProfitOrLossValue,
            ],
            'IssueOfShareCapital' => [
                'name' => 'Issue of Share Capital',
                'code' => 'ISS_SC',
                'share_capital' => $ShareCapitalIssued,
                'share_premium' => 0,
                'retained_earnings' => 0,
                'other_reserves' => 0,
                'total' => $ShareCapitalIssued,
            ],
            'OwnerShareholderContributions' => [
                'name' => 'Owner / Shareholder Contributions',
                'code' => 'CONTR',
                'share_capital' => 0,
                'share_premium' => 0,
                'retained_earnings' => 0,
                'other_reserves' => 0,
                'total' => 0,
            ],
            'DividendsDistributions' => [
                'name' => 'Dividends / Distributions',
                'code' => 'DIV',
                'share_capital' => 0,
                'share_premium' => 0,
                'retained_earnings' => -abs($DividendBalance['end_balance']),
                'other_reserves' => 0,
                'total' => -abs($DividendBalance['end_balance']),
            ],
            'OwnerDrawings' => [
                'name' => 'Owner Drawings (where applicable)',
                'code' => 'DRAW',
                'share_capital' => 0,
                'share_premium' => 0,
                'retained_earnings' => 0,
                'other_reserves' => 0,
                'total' => 0,
            ],
            'TransfersToFromReserves' => [
                'name' => 'Transfers to / from Reserves',
                'code' => 'TR',
                'share_capital' => 0,
                'share_premium' => 0,
                'retained_earnings' => 0,
                'other_reserves' => $OtherReservesMovement,
                'total' => $OtherReservesMovement,
            ],
            'PriorPeriodOpeningAdjustments' => [
                'name' => 'Prior-period / opening adjustments',
                'code' => 'ADJ',
                'share_capital' => 0,
                'share_premium' => 0,
                'retained_earnings' => 0,
                'other_reserves' => 0,
                'total' => 0,
            ],
            'BalanceAtEndOfPeriod' => [
                'name' => 'Balance at end of period',
                'code' => 'CLOSE',
                'share_capital' => $ShareCapitalBalance['end_balance'],
                'share_premium' => $SharePremiumBalance['end_balance'],
                'retained_earnings' => $RetainedEarningsBalance['end_balance'],
                'other_reserves' => $OtherReservesBalance['end_balance'],
                'total' => $TotalEquityEnd,
            ],
        ];

        return $particulars;
    }
}
