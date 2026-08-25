<?php

namespace App\Http\Controllers;

use App\Branch;
use App\Models\Budget;
use Illuminate\Http\Request;
use App\IncomeExpenseHead;

class BudgetController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $budgets = Budget::all();
            return response()->json($budgets);
        }
        $branches = Branch::all();
        $budgets = Budget::with('branch')->get();
        $accounts = IncomeExpenseHead::pluck('name', 'id');

        return view('pages.budgets.index', compact('budgets', 'accounts','branches'));
    }


    public function create()
    {
        // You can load the create view here
    }

    public function store(Request $request)
    {
        $accounts = $request->input('account');
        $branch = $request->input('branch_id');
        $months = $request->input('months');
        $annual_amounts = $request->input('annual_amount');

        foreach ($accounts as $key => $account) {
            Budget::create([
                'branch_id' => $branch,
                'account' => $account,
                'january' => $months[$key * 12 + 0],
                'february' => $months[$key * 12 + 1],
                'march' => $months[$key * 12 + 2],
                'april' => $months[$key * 12 + 3],
                'may' => $months[$key * 12 + 4],
                'june' => $months[$key * 12 + 5],
                'july' => $months[$key * 12 + 6],
                'august' => $months[$key * 12 + 7],
                'september' => $months[$key * 12 + 8],
                'october' => $months[$key * 12 + 9],
                'november' => $months[$key * 12 + 10],
                'december' => $months[$key * 12 + 11],
                'annual_amount' => $annual_amounts[$key],
            ]);
        }

        return redirect()->route('budgets.index')->with('success', 'Budget saved successfully.');
    }

    public function show($id)
    {
        $budget = Budget::find($id);
        return view('budgets.show', compact('budget'));
    }

    public function edit($id)
    {
        $budget = Budget::find($id);
        return view('budgets.edit', compact('budget'));
    }

    public function update(Request $request, $id)
    {
        $budget = Budget::find($id);
        $budget->update($request->all());
        return redirect()->route('budgets.index')->with('success', 'Budget updated successfully.');
    }

    public function destroy($id)
    {
        Budget::find($id)->delete();
        return redirect()->route('budgets.index')->with('success', 'Budget deleted successfully.');
    }
}
