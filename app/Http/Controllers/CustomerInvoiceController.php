<?php

namespace App\Http\Controllers;

use App\Constants\CustomerConstant;
use App\Constants\InvoiceConstant;
use App\Helpers\ArrayHelper;
use App\Helpers\BladeHelper;
use App\Helpers\ControllerHelper;
use App\IncomeExpenseType;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceItem;
use Illuminate\Http\Request;
use App\Models\InventoryType;
use App\Models\Inventory;
use App\Branch;
class CustomerInvoiceController extends Controller
{
    private $route;

    public function __construct()
    {
        $this->route = 'customers.invoices';
    }

    public function index()
    {
        try {
            // Fetch all customer invoices with their items
            $customerInvoices = CustomerInvoice::with('customer', 'items')->get();

            // Calculate total amount for each invoice
            foreach ($customerInvoices as $invoice) {
                $invoice->total_amount = $invoice->items->sum('amount');
            }

            return view('pages.customers.invoices.invoice-view', [
                'title' => 'Customer Invoices',
                'customerInvoices' => $customerInvoices,
                'resource' => 'Customer Invoices',
                'type' => 'list',
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load invoices. ' . $e->getMessage());
        }
    }

    public function create()
    {
        $customers = Customer::with('branch')->get();
        $invoices = InvoiceConstant::INVOICES;

        $nextInvoiceNumber = CustomerInvoice::nextInvoiceNumberPreview();

        // Fetch inventory items with necessary details including type name
        $inventory_items = Inventory::with('type:id,name')
            ->select('id', 'type_id', 'description', 'sale_price', 'quantity_at_hand')
            ->where('quantity_at_hand', '>', 0) // Filter out items with zero quantity
            ->get()
            ->map(function ($item) {
                $item->type_name = $item->type->name;
                unset($item->type);
                return $item;
            });

        return ControllerHelper::view([
            'title' => 'Add Invoice',
            'resource' => 'Customers',
            'type' => 'form',
            'action' => $this->route . '.store',
            'customers' => $customers,
            'customer_id_options' => BladeHelper::ddl_f($customers),
            'next_invoice_number' => $nextInvoiceNumber,
            'thead' => [
                '#',
                'Item',
                'Description',
                'Rate',
                'Qty',
                'Amount',
                'Action'
            ],
            'tbody' => ArrayHelper::arrayTop($invoices, 2),
            'inventory_items' => $inventory_items,
        ]);
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
            'customer_id' => 'required|exists:customers,id',
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
            // Lock and validate stock first. This prevents concurrent sales from
            // taking quantity below zero and ensures every sold item is deducted.
            foreach ($request->items as $item) {
                $inventoryItem = Inventory::whereKey($item['item_id'])->lockForUpdate()->firstOrFail();
                if ($inventoryItem->quantity_at_hand < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ['Insufficient stock for ' . ($inventoryItem->description ?: 'the selected item') . '.'],
                    ]);
                }
            }

            // Create a new invoice
            $invoice = new CustomerInvoice([
                'customer_id' => $request->customer_id,
                'invoice_date' => $request->invoice_date,
                'exchange_rate' => $request->exchange_rate,
            ]);

            $invoice->save();

            // Process invoice items and update inventory quantities
            $invoiceItems = [];
            foreach ($request->items as $item) {
                $invoiceItem = new CustomerInvoiceItem([
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
                $inventoryItem = Inventory::whereKey($item['item_id'])->lockForUpdate()->first();
                if ($inventoryItem) {
                    $newQuantity = $inventoryItem->quantity_at_hand - $item['quantity'];
                    $inventoryItem->update(['quantity_at_hand' => $newQuantity]);
                }
            }

            // Commit transaction
            DB::commit();

            // Prepare receipt data
            $customer = Customer::find($request->customer_id);

            // Calculate total amount for the invoice
            $totalAmount = collect($invoiceItems)->sum('amount');

            return view('pages.customers.invoices.receipt', [
                'invoice' => $invoice,
                'customer' => $customer,
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

    public function getInvoiceDetails($customerId) {
        // Assuming the model relationships and data fetching logic
        $customer = Customer::find($customerId);
        if (!$customer) {
            return redirect()->back()->with('error', 'Customer not found');
        }

        $invoice = CustomerInvoice::with('items.inventory.type')->where('customer_id', $customerId)->first();
        if (!$invoice) {
            return redirect()->back()->with('error', 'Invoice not found');
        }

        $totalAmount = $invoice->items->sum('amount');

        return view('customers.receipts', [
            'invoice' => $invoice,
            'totalAmount' => $totalAmount,
            'customer' => $customer,
        ]);
    }

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
