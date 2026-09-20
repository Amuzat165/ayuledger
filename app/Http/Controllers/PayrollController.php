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
        ]);

        Payroll::create($this->payrollData($validated));

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
        ]);

        $payroll->update($this->payrollData($validated));

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

    /** Calculate derived values on the server so form submission is reliable. */
    private function payrollData(array $data): array
    {
        $gross = $data['basic_salary'] + $data['housing_allowance'] + $data['transport_allowance']
            + $data['medical_allowance'] + $data['other_allowance'];
        $net = $gross - ($gross * $data['deduction_payee'] / 100) - ($gross * $data['pension'] / 100);

        return array_merge($data, [
            'gross_salary' => $gross,
            'net_pay' => $net,
        ]);
    }

}
