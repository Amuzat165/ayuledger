<?php

namespace App\Http\Controllers;

use App\BankCash;
use App\Branch;
use App\IncomeExpenseHead;
use App\Models\Expenses;
use App\Models\ExpensesType;
use App\Models\Vendor;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    protected $route = 'expenses';

    public function index()
    {
        $expenses = Expenses::with(['branch', 'bankCash'])->get();

        return view('pages.expenses.index', compact('expenses'));
    }

    public function create()
    {
        //Fetch all category
        $types = ExpensesType::all();

        $branches = Branch::all();

        // Fetch all vendors
        $vendors = Vendor::all();

        // Fetch a list of account names and their IDs
        $accounts = IncomeExpenseHead::pluck('name', 'id');

        // Fetch all bank cash entries
        $bankCashes = BankCash::all();

        // Pass the vendors and accounts data to the view
        return view('pages.expenses.create', compact('vendors', 'accounts', 'types', 'branches', 'bankCashes'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'type_id' => 'nullable|exists:expenses_types,id',
            'branch_id' => 'required|exists:branches,id',
            'new_type' => 'nullable|string|max:255',
            'account' => 'required|string|max:255',
            'vendor' => 'required|string|max:255',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'bank_cash_id' => 'nullable|exists:bank_cashes,id',
        ]);

        $typeId = $request->input('type_id');
        $newType = $request->input('new_type');

        if (!$typeId && $newType) {
            // Check for duplicate expenses type
            if (ExpensesType::where('name', $newType)->exists()) {
                return redirect()->back()->withErrors(['new_type' => 'category type already exists.'])->withInput();
            }

            $type = ExpensesType::create(['name' => $newType]);
            $typeId = $type->id;
        }

        Expenses::create([
            'type_id' => $typeId,
            'branch_id' => $request->input('branch_id'),
            'account' => $request->input('account'),
            'vendor' => $request->input('vendor'),
            'expense_date' => $request->input('expense_date'),
            'amount' => $request->input('amount'),
            'description' => $request->input('description'),
            'bank_cash_id' => $request->input('bank_cash_id'),
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense created successfully.');
    }

    public function edit($id)
    {
        $expense = Expenses::findOrFail($id);
        $types = ExpensesType::all();
        $accounts = IncomeExpenseHead::pluck('name', 'id');
        $vendors = Vendor::all();
        $branches = Branch::all();
        $bankCashes = BankCash::all();
        // Fetch any necessary data for the form (e.g., categories, accounts)
        // $categories = Category::all(); // Example placeholder
        return view('pages.expenses.edit', compact('expense','types','accounts','vendors','branches','bankCashes'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'type_id' => 'nullable|exists:inventory_types,id',
            'branch_id' => 'required|exists:branches,id',
            'new_type' => 'nullable|string|max:255',
            'account' => 'required|string|max:255',
            'vendor' => 'required|string|max:255',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'bank_cash_id' => 'nullable|exists:bank_cashes,id',
        ]);

        $expense = Expenses::findOrFail($id);

        $typeId = $request->input('type_id');

        if (!$typeId && $request->input('new_type')) {
            $type = ExpensesType::create(['name' => $request->input('new_type')]);
            $typeId = $type->id;
        }

        $expense->update([
            'type_id' => $typeId,
            'branch_id' => $request->input('branch_id'),
            'account' => $request->input('account'),
            'vendor' => $request->input('vendor'),
            'expense_date' => $request->input('expense_date'),
            'amount' => $request->input('amount'),
            'description' => $request->input('description'),
            'bank_cash_id' => $request->input('bank_cash_id'),
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy($id)
    {
        $expense = Expenses::findOrFail($id);
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully.');
    }
}
