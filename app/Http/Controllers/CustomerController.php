<?php

namespace App\Http\Controllers;

use App\Branch;
use App\Constants\CustomerConstant;
use App\Models\Customer;
use App\Helpers\ArrayHelper;
use App\Helpers\ControllerHelper;
use App\Http\Requests\StoreCustomerRequest;
use Illuminate\Http\Request;
use App\IncomeExpenseType;

class CustomerController extends Controller
{
    private $route;

    public function __construct()
    {
        $this->route = 'customers';
    }

    public function index()
    {
        $customers = true ? Customer::with('branch')->get() : CustomerConstant::CUSTOMERS;

        return ControllerHelper::view([
            'title' => 'View Customer',
            'resource' => 'Customers',
            'type' => 'table',
            //
            'thead' => [
                '#',
                'Branch',
                'Customer Name',
                'Email Address',
                'Phone Number',
                'Address',
                'Sales Ledger',
                'Terms of Payment',
//                'Date Created',
                'Edit',
                'Del'
            ],
            'tbody' => $customers,
        ]);
    }

    public function create()
    {
        $incomeExpenseTypes = IncomeExpenseType::select('id', 'name')->get();
        $sales_ledger_options = ArrayHelper::toAssoc($incomeExpenseTypes);
        $branches = Branch::select('id', 'name')->get();
        $branch_name = ArrayHelper::toAssoc($branches);

        return ControllerHelper::view([
            'title' => 'Add Customer',
            'resource' => 'Customers',
            'type' => 'form',
            'action' => $this->route . '.store',
            //
            "sales_ledger_options" => $sales_ledger_options,
            "branch_name" => $branch_name,
            'terms_of_payment_options' => ["COD", "Pay in number of days"],
            'defaults' => true ? [] : [
                'branch_id' => 'head office',
                'name' => 'john doe',
                'email' => 'john@email.com',
                'phone' => '12345678901',
                'address' => 'somewhere',
                'income_expense_type_id' => 4,
                'terms_of_payment' => "COD",
            ]
        ]);
    }

    public function store(StoreCustomerRequest $request)
    {
        // dd($validated);
        $validated = $request->validated();
        Customer::create($validated);

        return redirect('/customers')->with('success', 'customer created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);

        $incomeExpenseTypes = IncomeExpenseType::select('id', 'name')->get();
        $sales_ledger_options = ArrayHelper::toAssoc($incomeExpenseTypes);
        $branches = Branch::select('id', 'name')->get();
        $branch_name = ArrayHelper::toAssoc($branches);

        // Ensure $params is an object consistently
        $params = (object) [
            'title' => 'Edit Customer',
            'resource' => 'Customers',
            'type' => 'form',
            'action' => route('customers.update', $customer->id), // Form action for update
            'sales_ledger_options' => $sales_ledger_options,
            'terms_of_payment_options' => ["COD", "Pay in number of days"],
            "branch_name" => $branch_name,
            'defaults' => $customer
        ];

        return ControllerHelper::view($params);
    }


    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'address' => 'required',
            'income_expense_type_id' => 'required|exists:income_expense_types,id',
            'terms_of_payment' => 'required',
        ]);

        $customer->fill($validated)->save();

        return redirect()->route('customers.index')->with('success', 'customer updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect('/customers');
    }

}
