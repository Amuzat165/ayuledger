<?php

namespace App\Http\Controllers;

use App\Branch;
use Illuminate\Http\Request;
use App\Models\GeneralJournal;
use App\Models\Customer;
use App\IncomeExpenseHead;

class GeneralJournalController extends Controller
{
    public function index()
    {
        // Retrieve all journal entries
        $journalEntries = GeneralJournal::with('branch')->get();

        return view('pages.general-journal.index', [
            'title' => 'View General Journal Entries',
            'resource' => 'General Journal',
            'type' => 'table',
            'thead' => [
                '#',
                'Branch',
                'Account',
                'Debit',
                'Account',
                'Credit',
                'Memo',
                'Customer/Vendor',
                'Date Created',
                'Edit',
                'Delete'
            ],
            'tbody' => $journalEntries,
        ]);
    }

    public function create()
    {
        $customers = Customer::distinct()->get(['name']);
        $accounts = IncomeExpenseHead::pluck('name', 'id');
        $branches = Branch::pluck('name', 'id');

        $params = (object) [
            'title' => 'Add General Journal Entry',
            'resource' => 'General Journal',
            'type' => 'form',
            'action' => route('general-journal.store'),
            'defaults' => [
                'debit' => '',
                'credit' => '',
                'memo' => '',
                'customer_vendor' => '',
            ],
            'customers' => $customers,
            'accounts' => $accounts,
            'branches' => $branches
        ];

        return view('pages.general-journal.create', compact('params'));
    }


    public function store(Request $request)
    {
        // Validate and store the new journal entry
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'debit_account_id' => 'required|exists:income_expense_heads,id',
            'debit' => 'required|numeric',
            'credit_account_id' => 'required|exists:income_expense_heads,id',
            'credit' => 'required|numeric',
            'memo' => 'required',
            'customer_vendor' => 'required',
        ]);

        GeneralJournal::create($validated);

        return redirect()->route('general-journal.index')->with('success', 'Journal entry added successfully!');
    }

    public function edit($id)
    {
        $journalEntry = GeneralJournal::findOrFail($id);
        $customers = Customer::distinct()->get(['name']);
        $accounts = IncomeExpenseHead::pluck('name', 'id');
        $branches = Branch::all();

        $params = (object) [
            'title' => 'Edit General Journal Entry',
            'resource' => 'General Journal',
            'type' => 'form',
            'action' => route('general-journal.update', $journalEntry->id),
            'defaults' => [
                'debit_account_id' => $journalEntry->debit_account_id,
                'credit_account_id' => $journalEntry->credit_account_id,
                'debit' => $journalEntry->debit,
                'credit' => $journalEntry->credit,
                'memo' => $journalEntry->memo,
                'customer_vendor' => $journalEntry->customer_vendor,
            ],
            'customers' => $customers,
            'accounts' => $accounts,
            'branches' => $branches,
            'general_journal' => $journalEntry
        ];

        return view('pages.general-journal.edit', compact('params'));
    }

    public function update(Request $request, $id)
    {
        // Validate the updated journal entry
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'debit' => 'required|numeric',
            'credit' => 'required|numeric',
            'memo' => 'required',
            'customer_vendor' => 'required',
        ]);

        $journalEntry = GeneralJournal::findOrFail($id);
        unset($validated['debit_account_id']);
        unset($validated['credit_account_id']);
        $journalEntry->update($validated);

        // Flash success message to session
        return redirect()->route('general-journal.index')->with('success', 'Journal entry updated successfully!');
    }


    public function destroy($id)
    {
        $journalEntry = GeneralJournal::findOrFail($id);
        $journalEntry->delete();

        return redirect()->route('general-journal.index')->with('success', 'Journal deleted successfully.');
    }
}
