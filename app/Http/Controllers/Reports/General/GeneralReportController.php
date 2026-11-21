<?php

namespace App\Http\Controllers\Reports\General;

use App\Branch;
use App\Exports\GeneralReport\Ledger\Group;
use App\Exports\GeneralReport\Ledger\Name;
use App\Exports\GeneralReport\Ledger\Type;
use App\Exports\GeneralReport\BankCash as GeneralBankCash;

use App\Exports\GeneralReport\Voucher;
use App\IncomeExpenseGroup;
use App\IncomeExpenseType;
use DateTime;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


use App\IncomeExpenseHead;
use App\BankCash;
use App\Transaction;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Barryvdh\DomPDF\Facade as PDF;
use App\Http\Controllers\RoleManageController;
use App\Setting;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

use App\Exports\GeneralReport\Branch as GeneralBranchReport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Services\GeneralLedgerReportService;


class GeneralReportController extends Controller
{
    public function branch()
    {
        return view('admin.general-report.branch.index');
    }

    /**
     * @param Request $request
     * @return Factory|View|BinaryFileResponse
     */
    public function branch_report(Request $request)
    {
        $now = new DateTime();
        $date = $now->format(Config('settings.date_format') . ' h:i:s');

        $extra = array(
            'current_date_time' => $date,
            'module_name' => 'Date Wise Branch Report',
            'voucher_type' => 'BRANCH REPORT'
        );

        if (!empty($request->from)) {

            $dateFrom = new DateTime($request->from);
            $From = $dateFrom->format('Y-m-d'); // 31-07-2012 '2008-11-11'

            $dateTo = new DateTime($request->to);
            $To = $dateTo->format('Y-m-d'); // 31-07-2012 '2008-11-11'


            $items = Branch::whereBetween('created_at', [$From . " 00:00:00", $To . " 23:59:59"])
                ->orderBy('created_at', 'asc')
                ->get();

            $from = $dateFrom->format('d/m/Y');
            $to = $dateTo->format('d/m/Y');
        } else {

            $items = Branch::all();


            $from = 'UpTo ' . $date;
            $to = 'UpTo ' . $date;
        }

        if (count($items) < 1) {
            Session::flash('error', 'No Data Found');
            return redirect()->back();
        }


        $search_by = array(
            'from' => $from,
            'to' => $to,
        );


        // Show Action
        if ($request->action == 'Show') {
            return view('admin.general-report.branch.date-wise.index')
                ->with('items', $items)
                ->with('extra', $extra)
                ->with('search_by', $search_by);
        }

        // Pdf Action
        if ($request->action == 'Pdf') {

            $pdf = PDF::loadView('admin.general-report.branch.date-wise.pdf', [
                'items' => $items,
                'extra' => $extra,
                'search_by' => $search_by,
            ])
                ->setPaper('a4', 'landscape');

            //return $pdf->stream(date(config('settings.date_format'), strtotime($extra['current_date_time'])) . '_' . $extra['module_name'] . '.pdf');
            return $pdf->download($extra['current_date_time'] . '_' . $extra['module_name'] . '.pdf');
        }

        // Excel Action
        if ($request->action == 'Excel') {

            $BranchWise = new GeneralBranchReport([
                'items' => $items,
                'extra' => $extra,
                'search_by' => $search_by,
            ]);
            return Excel::download($BranchWise, $extra['current_date_time'] . '_' . $extra['module_name'] . '.xlsx');
        }
    }


    public function ledger_type()
    {
        return view('admin.general-report.ledger.index', ['branches' => Branch::orderBy('name')->get()]);
    }


    public function ledger_type_report(Request $request)
    {

        $now = new DateTime();
        $date = $now->format(Config('settings.date_format') . ' h:i:s');

        $extra = array(
            'current_date_time' => $date,
            'module_name' => 'Date Wise Ledger Type Report',
            'voucher_type' => 'LEDGER TYPE REPORT'
        );

        if (!empty($request->from)) {

            $dateFrom = new DateTime($request->from);
            $From = $dateFrom->format('Y-m-d'); // 31-07-2012 '2008-11-11'

            $dateTo = new DateTime($request->to);
            $To = $dateTo->format('Y-m-d'); // 31-07-2012 '2008-11-11'


            $items = IncomeExpenseType::whereBetween('created_at', [$From . " 00:00:00", $To . " 23:59:59"])
                ->orderBy('created_at', 'asc')
                ->get();

            $from = $dateFrom->format('d/m/Y');
            $to = $dateTo->format('d/m/Y');
        } else {

            $items = IncomeExpenseType::all();


            $from = 'UpTo ' . $date;
            $to = 'UpTo ' . $date;
        }

        if (count($items) < 1) {
            Session::flash('error', 'No Data Found');
            return redirect()->back();
        }


        $search_by = array(
            'from' => $from,
            'to' => $to,
        );


        // Show Action
        if ($request->action == 'Show') {
            return view('admin.general-report.ledger.type.date-wise.index')
                ->with('items', $items)
                ->with('extra', $extra)
                ->with('search_by', $search_by);
        }

        // Pdf Action
        if ($request->action == 'Pdf') {

            $pdf = PDF::loadView('admin.general-report.ledger.type.date-wise.pdf', [
                'items' => $items,
                'extra' => $extra,
                'search_by' => $search_by,
            ])
                ->setPaper('a4', 'landscape');

            //return $pdf->stream(date(config('settings.date_format'), strtotime($extra['current_date_time'])) . '_' . $extra['module_name'] . '.pdf');
            return $pdf->download($extra['current_date_time'] . '_' . $extra['module_name'] . '.pdf');
        }

        // Excel Action
        if ($request->action == 'Excel') {

            $BranchWise = new Type([
                'items' => $items,
                'extra' => $extra,
                'search_by' => $search_by,
            ]);
            return Excel::download($BranchWise, $extra['current_date_time'] . '_' . $extra['module_name'] . '.xlsx');
        }
    }


    public function ledger_group_report(Request $request)
    {

        $now = new DateTime();
        $date = $now->format(Config('settings.date_format') . ' h:i:s');

        $extra = array(
            'current_date_time' => $date,
            'module_name' => 'Date Wise Ledger Group Report',
            'voucher_type' => 'LEDGER GROUP REPORT'
        );

        if (!empty($request->from)) {

            $dateFrom = new DateTime($request->from);
            $From = $dateFrom->format('Y-m-d'); // 31-07-2012 '2008-11-11'

            $dateTo = new DateTime($request->to);
            $To = $dateTo->format('Y-m-d'); // 31-07-2012 '2008-11-11'


            $items = IncomeExpenseGroup::whereBetween('created_at', [$From . " 00:00:00", $To . " 23:59:59"])
                ->orderBy('created_at', 'asc')
                ->get();

            $from = $dateFrom->format('d/m/Y');
            $to = $dateTo->format('d/m/Y');
        } else {

            $items = IncomeExpenseGroup::all();


            $from = 'UpTo ' . $date;
            $to = 'UpTo ' . $date;
        }

        if (count($items) < 1) {
            Session::flash('error', 'No Data Found');
            return redirect()->back();
        }


        $search_by = array(
            'from' => $from,
            'to' => $to,
        );


        // Show Action
        if ($request->action == 'Show') {
            return view('admin.general-report.ledger.group.date-wise.index')
                ->with('items', $items)
                ->with('extra', $extra)
                ->with('search_by', $search_by);
        }

        // Pdf Action
        if ($request->action == 'Pdf') {

            $pdf = PDF::loadView('admin.general-report.ledger.group.date-wise.pdf', [
                'items' => $items,
                'extra' => $extra,
                'search_by' => $search_by,
            ])
                ->setPaper('a4', 'landscape');

            //return $pdf->stream(date(config('settings.date_format'), strtotime($extra['current_date_time'])) . '_' . $extra['module_name'] . '.pdf');
            return $pdf->download($extra['current_date_time'] . '_' . $extra['module_name'] . '.pdf');
        }

        // Excel Action
        if ($request->action == 'Excel') {

            $BranchWise = new Group([
                'items' => $items,
                'extra' => $extra,
                'search_by' => $search_by,
            ]);
            return Excel::download($BranchWise, $extra['current_date_time'] . '_' . $extra['module_name'] . '.xlsx');
        }
    }

    public function ledger_name_report(Request $request)
    {
        $request->validate([
            'branch_id' => 'nullable|integer|exists:branches,id',
            'action' => 'required|in:Show,Pdf,Excel',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $now = new DateTime();
        $fromDate = $request->filled('from') ? (new DateTime($request->from))->format('Y-m-d') : '2024-01-01';
        $toDate = $request->filled('to') ? (new DateTime($request->to))->format('Y-m-d') : $now->format('Y-m-d');
        $items = (new GeneralLedgerReportService())->transactions($fromDate . ' 00:00:00', $toDate . ' 23:59:59', $request->branch_id);

        if ($items->isEmpty()) {
            Session::flash('error', 'No Data Found');
            return redirect()->back();
        }

        $extra = [
            'current_date_time' => $now->format(config('settings.date_format') . ' h:i:s'),
            'module_name' => 'Ledger Name',
            'voucher_type' => 'LEDGER NAME REPORT',
        ];
        $searchBy = [
            'from' => \DateTime::createFromFormat('Y-m-d', $fromDate)->format('d/m/Y'),
            'to' => \DateTime::createFromFormat('Y-m-d', $toDate)->format('d/m/Y'),
            'branch_name' => $request->branch_id ? optional(Branch::find($request->branch_id))->name : 'All Branches',
        ];

        if ($request->action === 'Show') {
            return view('admin.general-report.ledger.name.date-wise.index', compact('items', 'extra', 'searchBy'))
                ->with('search_by', $searchBy);
        }

        if ($request->action === 'Pdf') {
            return PDF::loadView('admin.general-report.ledger.name.date-wise.pdf', [
                'items' => $items,
                'extra' => $extra,
                'search_by' => $searchBy,
            ])->setPaper('a4', 'landscape')->download($extra['current_date_time'] . '_Ledger Name.pdf');
        }

        return Excel::download(new Name([
            'items' => $items,
            'extra' => $extra,
            'search_by' => $searchBy,
        ]), $extra['current_date_time'] . '_Ledger Name.xlsx');
    }


    public function bank_cash()
    {
        return view('admin.general-report.bank-cash.index', ['branches' => Branch::orderBy('name')->get()]);
    }


    /**
     * @param Request $request
     * @return Factory|RedirectResponse|View|BinaryFileResponse
     */
    public function bank_cash_report(Request $request)
    {

        $now = new DateTime();
        $date = $now->format(Config('settings.date_format') . ' h:i:s');

        $extra = array(
            'current_date_time' => $date,
            'module_name' => 'Bank Cash Report',
            'voucher_type' => 'BANK CASH REPORT'
        );

        if (!empty($request->from)) {

            $dateFrom = new DateTime($request->from);
            $From = $dateFrom->format('Y-m-d'); // 31-07-2012 '2008-11-11'

            $dateTo = new DateTime($request->to);
            $To = $dateTo->format('Y-m-d'); // 31-07-2012 '2008-11-11'


            $items = BankCash::whereBetween('created_at', [$From . " 00:00:00", $To . " 23:59:59"])
                ->orderBy('created_at', 'asc')
                ->get();

            $from = $dateFrom->format('d/m/Y');
            $to = $dateTo->format('d/m/Y');
        } else {

            $items = BankCash::all();


            $from = 'UpTo ' . $date;
            $to = 'UpTo ' . $date;
        }

        if (count($items) < 1) {
            Session::flash('error', 'No Data Found');
            return redirect()->back();
        }


        $search_by = array(
            'from' => $from,
            'to' => $to,
        );


        // Show Action
        if ($request->action == 'Show') {
            return view('admin.general-report.bank-cash.date-wise.index')
                ->with('items', $items)
                ->with('extra', $extra)
                ->with('search_by', $search_by);
        }

        // Pdf Action
        if ($request->action == 'Pdf') {

            $pdf = PDF::loadView('admin.general-report.bank-cash.date-wise.pdf', [
                'items' => $items,
                'extra' => $extra,
                'search_by' => $search_by,
            ])
                ->setPaper('a4', 'landscape');

            //return $pdf->stream(date(config('settings.date_format'), strtotime($extra['current_date_time'])) . '_' . $extra['module_name'] . '.pdf');
            return $pdf->download($extra['current_date_time'] . '_' . $extra['module_name'] . '.pdf');
        }

        // Excel Action
        if ($request->action == 'Excel') {

            $BranchWise = new GeneralBankCash([
                'items' => $items,
                'extra' => $extra,
                'search_by' => $search_by,
            ]);
            return Excel::download($BranchWise, $extra['current_date_time'] . '_' . $extra['module_name'] . '.xlsx');
        }
    }


    public function voucher()
    {
        return view('admin.general-report.voucher.index', ['branches' => Branch::orderBy('name')->get()]);
    }


    public function voucher_report(Request $request)
    {

        $now = new DateTime();
        $date = $now->format(Config('settings.date_format') . ' h:i:s');
        $extra = array(
            'current_date_time' => $date,
            'module_name' => 'Voucher Report',
            'voucher_type' => 'VOUCHER REPORT'
        );
        $all = 'all';
        if (empty($request->from) and empty($request->voucher_type)) {
            $items = Transaction::with('Branch', 'IncomeExpenseHead', 'BankCash')->get();
            $from = 'UpTo ' . $date;
            $to = 'UpTo ' . $date;
        } elseif (!empty($request->from) and !empty($request->voucher_type)) {
            $dateFrom = new DateTime($request->from);
            $From = $dateFrom->format('Y-m-d'); // 31-07-2012 '2008-11-11'
            $dateTo = new DateTime($request->to);
            $To = $dateTo->format('Y-m-d'); // 31-07-2012 '2008-11-11'
            $items = Transaction::with('Branch', 'IncomeExpenseHead', 'BankCash')->where('voucher_type', $request->voucher_type)
                ->whereBetween('voucher_date', [$From . " 00:00:00", $To . " 23:59:59"])
                ->orderBy('created_at', 'asc')
                ->get();

            $from = $dateFrom->format('d/m/Y');
            $to = $dateTo->format('d/m/Y');
            $all = $request->voucher_type;
        } elseif (empty($request->from) and !empty($request->voucher_type)) {

            $items = Transaction::with('Branch', 'IncomeExpenseHead', 'BankCash')->where('voucher_type', $request->voucher_type)
                ->orderBy('created_at', 'asc')
                ->get();

            $from = 'UpTo ' . $date;
            $to = 'UpTo ' . $date;
            $all = $request->voucher_type;
        } else {
            $dateFrom = new DateTime($request->from);
            $From = $dateFrom->format('Y-m-d'); // 31-07-2012 '2008-11-11'
            $dateTo = new DateTime($request->to);
            $To = $dateTo->format('Y-m-d'); // 31-07-2012 '2008-11-11'
            $items = Transaction::with('Branch', 'IncomeExpenseHead', 'BankCash')->whereBetween('voucher_date', [$From . " 00:00:00", $To . " 23:59:59"])
                ->orderBy('created_at', 'asc')
                ->get();
            $from = $dateFrom->format('d/m/Y');
            $to = $dateTo->format('d/m/Y');
        }
        if ($request->branch_id) {
            $items = $items->where('branch_id', $request->branch_id);
        }
        if (count($items) < 1) {
            Session::flash('error', 'No Data Found');
            return redirect()->back();
        }
        $search_by = array(
            'from' => $from,
            'to' => $to,
            'type_name' => $all,
            'branch_name' => $request->branch_id ? optional(Branch::find($request->branch_id))->name : 'All Branches',
        );
        // Show Action
        if ($request->action == 'Show') {
            return view('admin.general-report.voucher.date-wise.index')
                ->with('items', $items)
                ->with('extra', $extra)
                ->with('search_by', $search_by);
        }
        // Pdf Action
        if ($request->action == 'Pdf') {

            $pdf = PDF::loadView('admin.general-report.voucher.date-wise.pdf', [
                'items' => $items,
                'extra' => $extra,
                'search_by' => $search_by,
            ])
                ->setPaper('a4', 'landscape');

            //return $pdf->stream(date(config('settings.date_format'), strtotime($extra['current_date_time'])) . '_' . $extra['module_name'] . '.pdf');
            return $pdf->download($extra['current_date_time'] . '_' . $extra['module_name'] . '.pdf');
        }

        // Excel Action
        if ($request->action == 'Excel') {

            $BranchWise = new Voucher([
                'items' => $items,
                'extra' => $extra,
                'search_by' => $search_by,
            ]);
            return Excel::download($BranchWise, $extra['current_date_time'] . '_' . $extra['module_name'] . '.xlsx');
        }
    }
}
