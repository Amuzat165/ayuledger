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
        $title = __('financial.screens.payrolls'); // Set your desired title here
        $payrolls = Payroll::with('branch')->get();

        return view('pages.payrolls.index', [
            'title' => $title,
            'resource' => __('financial.screens.payrolls'), // Optional: Adjust based on your breadcrumb requirements
            'type' => 'table', // Optional: Adjust based on your breadcrumb requirements
            'thead' => [ // Your table headers array
                '#',
                __('financial.labels.branch'),
                __('financial.screens.employee'),
                __('financial.screens.basic_salary'),
                __('financial.screens.housing'),
                __('financial.screens.transport'),
                __('financial.screens.medical'),
                __('financial.screens.other_benefits'),
                __('financial.screens.gross_salary'),
                __('financial.screens.deduction_payee'),
                __('financial.screens.pension'),
                __('financial.screens.net_pay'),
//                'Date Created',
                __('root.common.edit'),
                __('ui.actions.delete')
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
            'title' => __('financial.screens.add_payroll'),
            'resource' => __('financial.screens.payrolls'),
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

        return redirect()->route('payrolls.index')->with('success', __('financial.screens.payroll_added'));
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
            'title' => __('financial.screens.view_payroll'),
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

        return redirect()->route('payrolls.index')->with('success', __('financial.screens.payroll_updated'));
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
        return redirect()->route('payrolls.index')->with('success', __('financial.screens.payroll_deleted'));
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
