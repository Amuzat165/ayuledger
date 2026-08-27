<?php

namespace App\Http\Controllers\Reports;

use App\Branch;
use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Payroll;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;

class PayrollBudgetReportController extends Controller
{
    public function payroll()
    {
        return view('admin.accounts-report.payroll.index', ['branches' => $this->branchOptions()]);
    }

    public function payrollReport(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $query = Payroll::with('branch')->orderBy('created_at');
        $this->applyFilters($query, $filters);
        $items = $query->get();

        return $this->respond($request, 'Payroll Report', 'admin.accounts-report.payroll.report', [
            'items' => $items,
            'filters' => $filters,
            'totalGross' => $items->sum('gross_salary'),
            'totalNet' => $items->sum('net_pay'),
        ]);
    }

    public function budget()
    {
        return view('admin.accounts-report.budget.index', ['branches' => $this->branchOptions()]);
    }

    public function budgetReport(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $query = Budget::with('branch')->orderBy('account');
        $this->applyFilters($query, $filters);
        $items = $query->get();

        return $this->respond($request, 'Budget Report', 'admin.accounts-report.budget.report', [
            'items' => $items,
            'filters' => $filters,
            'totalBudget' => $items->sum('annual_amount'),
        ]);
    }

    private function validatedFilters(Request $request)
    {
        return $request->validate([
            'branch_id' => 'nullable|integer|exists:branches,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'action' => 'required|in:Show,Pdf',
        ]);
    }

    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
    }

    private function respond(Request $request, $title, $view, array $data)
    {
        $data['title'] = $title;
        $data['branch'] = !empty($data['filters']['branch_id'])
            ? Branch::find($data['filters']['branch_id'])
            : null;

        if ($request->input('action') === 'Pdf') {
            return PDF::loadView($view, $data)->setPaper('a4', 'landscape')->download($title . '.pdf');
        }

        return view($view, $data);
    }

    private function branchOptions()
    {
        return Branch::orderBy('name')
            ->get()
            ->mapWithKeys(function ($branch) {
                return [$branch->id => $branch->name];
            });
    }
}
