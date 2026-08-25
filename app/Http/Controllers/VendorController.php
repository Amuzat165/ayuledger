<?php

namespace App\Http\Controllers;

use App\Branch;
use Illuminate\Http\Request;
use App\Models\Vendor;
use App\IncomeExpenseHead;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Retrieve all vendors
        $vendors = Vendor::with('branch')->get();

        return view('pages.vendors.index', [
            'title' => 'View Vendors',
            'resource' => 'Vendors',
            'type' => 'table',
            'thead' => [
                '#',
                'ID',
                'Branch',
                'Name',
                'Address',
                'Phone no',
                'Account',
                'Terms of Payment',
//                'Date Created',
                'Edit',
                'Del'
            ],
            'tbody' => $vendors,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $accounts = IncomeExpenseHead::pluck('name', 'id');
        $branches = Branch::pluck('name', 'id');

        $params = [
            'title' => 'Add Vendor',
            'resource' => 'Vendors',
            'type' => 'form',
            'action' => route('vendors.store'),
            'defaults' => [
                'name' => '',
                'address' => '',
                'phone_number' => '',
                'account' => '',
                'terms_of_payment' => '',
            ],
            'accounts' => $accounts,
            'branches' => $branches
        ];

        // Ensure $vendor or any other variable is correctly initialized if needed

        return view('pages.vendors.create', compact('params'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'account' => 'required|string|max:255',
            'terms_of_payment' => 'required|string|max:255',
            'days' => 'nullable|integer|min:1',
        ]);

        $termsOfPayment = $request->terms_of_payment === 'days' ? $request->days . ' days' : $request->terms_of_payment;

        Vendor::create([
            'branch_id' => $request->input('branch_id'),
            'name' => $request->name,
            'address' => $request->address,
            'phone_number' => $request->phone_number,
            'account' => $request->account,
            'terms_of_payment' => $termsOfPayment,
        ]);

        return redirect()->route('vendors.index')->with('success', 'Vendor created successfully.');
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // Example in your controller

    public function show($id)
    {
        $vendor = Vendor::findOrFail($id); // Assuming Vendor is an Eloquent model

        return view('vendor.show', compact('vendor'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $vendor = Vendor::findOrFail($id);
        $branches = Branch::pluck('name', 'id');
        $accounts = IncomeExpenseHead::pluck('name', 'id');

        $params = (object) [
            'title' => 'Edit Vendor',
            'resource' => 'Vendors',
            'type' => 'form',
            'action' => route('vendors.update', $vendor->id),
            'defaults' => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'address' => $vendor->address,
                'phone_number' => $vendor->phone_number,
                'account' => $vendor->account,
                'terms_of_payment' => $vendor->terms_of_payment,
                'branch_id' => $vendor->branch_id ?? null,
            ],
            'accounts' => $accounts,
            'branches' => $branches
        ];

        return view('pages.vendors.edit', compact('params'));
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
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'account' => 'required|string|max:255',
            'terms_of_payment' => 'required|string|max:255',
        ]);

        $vendor = Vendor::findOrFail($id);
        $vendor->update($request->all());

        return redirect()->route('vendors.index')->with('success', 'Vendor updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();

        return redirect()->route('vendors.index')->with('success', 'Vendor deleted successfully.');
    }
}
