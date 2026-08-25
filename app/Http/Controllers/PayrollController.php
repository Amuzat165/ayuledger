<?php

namespace App\Http\Controllers;

use App\Branch;
use App\Models\Payroll;
use Illuminate\Http\Request;
use App\Helpers\ControllerHelper;

class PayrollController extends Controller
{
    private $route;

    public function __construct()
    {
        $this->route = 'payrolls';
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        $title = 'Payrolls'; // Set your desired title here
        $payrolls = Payroll::with('branch')->get();

        return view('pages.payrolls.index', [
            'title' => $title,
            'resource' => 'Payrolls', // Optional: Adjust based on your breadcrumb requirements
            'type' => 'table', // Optional: Adjust based on your breadcrumb requirements
            'thead' => [ // Your table headers array
                '#',
                'Branch',
                'Employee',
                'Basic Salary',
                'Housing',
                'Transport',
                'Medical',
                'Other Benefits',
                'Gross Salary',
                'Deduction Payee',
                'Pension',
                'Net Pay',
//                'Date Created',
                'Edit',
                'Del'
            ],
            'tbody' => $payrolls, // Your table body data
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {
        $branches = Branch::pluck('name', 'id');

        $params = [
            'title' => 'Add Payroll',
            'resource' => 'Payrolls',
            'type' => 'form',
            'action' => route($this->route . '.store'),
            'defaults' => [],
            'branches' => $branches
        ];

        // Ensure $vendor or any other variable is correctly initialized if needed

        return view('pages.payrolls.create', compact('params'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'employee' => 'required|string',
            'basic_salary' => 'required|numeric',
            'housing_allowance' => 'required|numeric',
            'transport_allowance' => 'required|numeric',
            'medical_allowance' => 'required|numeric',
            'other_allowance' => 'required|numeric',
            'deduction_payee' => 'required|numeric',
            'pension' => 'required|numeric',
            'gross_salary' => 'required|numeric',
            'net_pay' => 'required|numeric',
        ]);

        // Create Payroll record
        $payroll = new Payroll([
            'branch_id' => $request->input('branch_id'),
            'employee' => $validated['employee'],
            'basic_salary' => $validated['basic_salary'],
            'housing_allowance' => $validated['housing_allowance'],
            'transport_allowance' => $validated['transport_allowance'],
            'medical_allowance' => $validated['medical_allowance'],
            'other_allowance' => $validated['other_allowance'],
            'deduction_payee' => $validated['deduction_payee'],
            'pension' => $validated['pension'],
            'gross_salary' => $validated['gross_salary'],
            'net_pay' => $validated['net_pay'],
        ]);

        $payroll->save();

        return redirect()->route('payrolls.index')->with('success', 'Payroll entry added successfully!');
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $payroll = Payroll::findOrFail($id);

        return ControllerHelper::view([
            'title' => 'View Payroll',
            'resource' => 'Payrolls',
            'type' => 'detail',
            'data' => $payroll
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $payroll = Payroll::findOrFail($id);
        $branches = Branch::all();

        return view('pages.payrolls.edit', compact('payroll', 'branches'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $payroll = Payroll::findOrFail($id);

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'employee' => 'required|string',
            'basic_salary' => 'required|numeric',
            'housing_allowance' => 'required|numeric',
            'transport_allowance' => 'required|numeric',
            'medical_allowance' => 'required|numeric',
            'other_allowance' => 'required|numeric',
            'deduction_payee' => 'required|numeric',
            'pension' => 'required|numeric',
            'gross_salary' => 'required|numeric',
            'net_pay' => 'required|numeric',
        ]);

        // Update Payroll record
        $payroll->update([
            'branch_id' => $request->input('branch_id'),
            'employee' => $validated['employee'],
            'basic_salary' => $validated['basic_salary'],
            'housing_allowance' => $validated['housing_allowance'],
            'transport_allowance' => $validated['transport_allowance'],
            'medical_allowance' => $validated['medical_allowance'],
            'other_allowance' => $validated['other_allowance'],
            'deduction_payee' => $validated['deduction_payee'],
            'pension' => $validated['pension'],
            'gross_salary' => $validated['gross_salary'],
            'net_pay' => $validated['net_pay'],
        ]);

        return redirect()->route('payrolls.index')->with('success', 'Payroll entry updated successfully!');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $payroll = Payroll::findOrFail($id);
        $payroll->delete();
        return redirect()->route('payrolls.index')->with('success', 'Payroll deleted successfully.');
    }

}
