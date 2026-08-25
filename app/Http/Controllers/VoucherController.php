<?php

namespace App\Http\Controllers;

use App\Constants\InvoiceConstant;
use App\Helpers\ArrayHelper;
use App\Helpers\BladeHelper;
use App\Helpers\ControllerHelper;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceItem;
use App\Models\Inventory;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorInvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    protected $route = 'vouchers';
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        try {
            // Fetch all vendor invoices with their items
            $vendorInvoices = VendorInvoice::with('vendor', 'items')->get();

            // Calculate total amount for each invoice
            foreach ($vendorInvoices as $invoice) {
                $invoice->total_amount = $invoice->items->sum('amount');
            }

            $resource = 'Vendor Invoices';

            return view('pages.vendors.purchase-order-view', [
                'title' => 'Vendor Invoices',
                'vendorInvoices' => $vendorInvoices,
                'resource' => $resource,
                'type' => 'list',
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load invoices. ' . $e->getMessage());
        }
    }



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {
        try {

            $vendors = Vendor::with('branch')->get();

            $nextInvoiceNumber = VendorInvoice::nextInvoiceNumberPreview();

            // Fetch inventory items with necessary details including type name
            $inventory_items = Inventory::with('type:id,name')
                ->select('id', 'type_id', 'description', 'unit_cost', 'quantity_at_hand')
                ->where('quantity_at_hand', '>', 0) // Filter out items with zero quantity
                ->get()
                ->map(function ($item) {
                    $item->type_name = $item->type->name; // Add 'type_name' attribute
                    unset($item->type); // Remove 'type' object to keep the response cleaner
                    return $item;
                });

            return view('pages.vendors.purchase-order', [
                'title' => 'Add Invoice',
                'resource' => 'Vendor',
                'type' => 'form',
                'action' => route($this->route . '.store'),
                'vendors' => $vendors,
                'vendor_id_options' => $vendors->pluck('name', 'id')->toArray(), // Ensure this returns an array of options
                'next_invoice_number' => $nextInvoiceNumber,
                'inventory_items' => $inventory_items,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load invoice creation form. ' . $e->getMessage());
        }
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'invoice_date' => 'required|date',
            'exchange_rate' => 'required|numeric',
            'items.*.item_id' => 'required|exists:inventories,id',
            'items.*.description' => 'nullable|string',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.amount' => 'required|numeric|min:0',
        ]);

        // Start a transaction
        DB::beginTransaction();

        try {
            // Create a new invoice
            $invoice = new VendorInvoice([
                'vendor_id' => $request->vendor_id,
                'invoice_date' => $request->invoice_date,
                'exchange_rate' => $request->exchange_rate,
            ]);

            $invoice->save();

            // Process invoice items and update inventory quantities
            $invoiceItems = [];
            foreach ($request->items as $item) {
                $invoiceItem = new VendorInvoiceItem([
                    'invoice_id' => $invoice->id,
                    'item_id' => $item['item_id'],
                    'description' => $item['description'],
                    'rate' => $item['rate'],
                    'quantity' => $item['quantity'],
                    'amount' => $item['amount'],
                ]);

                $invoiceItem->save();
                $invoiceItems[] = $invoiceItem;

                // Update inventory quantity at hand
                $inventoryItem = Inventory::find($item['item_id']);
                if ($inventoryItem) {
                    $newQuantity = $inventoryItem->quantity_at_hand + $item['quantity'];
                    $inventoryItem->update(['quantity_at_hand' => $newQuantity]);
                }
            }

            // Commit transaction
            DB::commit();

            // Prepare receipt data
            $vendor = Vendor::find($request->vendor_id);

            // Calculate total amount for the invoice
            $totalAmount = collect($invoiceItems)->sum('amount');

            return view('pages.vendors.receipt', [
                'invoice' => $invoice,
                'vendor' => $vendor,
                'invoiceItems' => $invoiceItems,
                'totalAmount' => $totalAmount, // Include total amount in the view
            ])->with('success', 'Invoice created successfully.');
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to create invoice. ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
