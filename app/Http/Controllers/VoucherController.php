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
use App\Support\VendorWithholdingCalculator;
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
                'currency_symbol' => config('settings.currency_symbol'),
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
                'wht_enabled' => (bool) config('settings.wht_enabled', false),
                'wht_mode' => config('settings.wht_mode', 'manual'),
                'wht_manual_rate' => config('settings.wht_manual_rate', 0),
                'currency_symbol' => config('settings.currency_symbol'),
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
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'invoice_date' => 'required|date',
            'exchange_rate' => 'required|numeric',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|exists:inventories,id',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // The form presents Inventory.unit_cost as a read-only rate. Re-read it here;
        // client amount, rate, total, balance, and WHT fields are never authoritative.
        $inventoryItems = Inventory::whereIn('id', collect($request->input('items'))->pluck('item_id')->unique())
            ->get()
            ->keyBy('id');
        $calculationLines = [];
        foreach ($request->input('items') as $item) {
            $inventoryItem = $inventoryItems->get((int) $item['item_id']);
            if (!$inventoryItem) {
                return redirect()->back()->withErrors(['items' => ['A selected inventory item is no longer available.']])->withInput();
            }

            $calculationLines[] = [
                'item_id' => $inventoryItem->id,
                'description' => $item['description'] ?? $inventoryItem->description,
                'quantity' => $item['quantity'],
                'rate' => $inventoryItem->unit_cost,
            ];
        }

        $financial = app(VendorWithholdingCalculator::class)->calculate($calculationLines, [
            'wht_enabled' => config('settings.wht_enabled', false),
            'wht_mode' => config('settings.wht_mode', 'manual'),
            'wht_manual_rate' => config('settings.wht_manual_rate', '0.00'),
        ]);

        DB::beginTransaction();
        try {
            $invoice = new VendorInvoice([
                'vendor_id' => $request->vendor_id,
                'invoice_date' => $request->invoice_date,
                'exchange_rate' => $request->exchange_rate,
                // Compatibility amount remains the single authoritative WHT amount for new invoices.
                'withholding_tax_amount' => $financial['withholding_tax_amount'],
                'wht_enabled_snapshot' => $financial['wht_enabled_snapshot'],
                'wht_mode_snapshot' => $financial['wht_mode_snapshot'],
                'wht_rate_snapshot' => $financial['wht_rate_snapshot'],
                'wht_base_snapshot' => $financial['wht_base_snapshot'],
                'wht_balance_due_snapshot' => $financial['wht_balance_due_snapshot'],
                'wht_calculation_version' => $financial['wht_calculation_version'],
                'wht_calculated_at' => now(),
            ]);
            $invoice->save();

            $invoiceItems = [];
            foreach ($financial['lines'] as $line) {
                $invoiceItem = new VendorInvoiceItem([
                    'invoice_id' => $invoice->id,
                    'item_id' => $line['item_id'],
                    'description' => $line['description'],
                    'rate' => $line['rate'],
                    'quantity' => $line['quantity'],
                    'amount' => $line['amount'],
                ]);
                $invoiceItem->save();
                $invoiceItems[] = $invoiceItem;

                $inventoryItem = $inventoryItems->get($line['item_id']);
                $inventoryItem->update([
                    'quantity_at_hand' => $inventoryItem->quantity_at_hand + $line['quantity'],
                ]);
            }

            DB::commit();

            $vendor = Vendor::find($request->vendor_id);
            return view('pages.vendors.receipt', [
                'invoice' => $invoice,
                'vendor' => $vendor,
                'invoiceItems' => $invoiceItems,
                'totalAmount' => $financial['vendor_total'],
                'balanceDue' => $financial['wht_balance_due_snapshot'],
                'currency_symbol' => config('settings.currency_symbol'),
            ])->with('success', 'Invoice created successfully.');
        } catch (\Exception $e) {
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
