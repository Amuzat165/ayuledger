<?php

namespace App\Http\Controllers;

use App\Constants\CustomerConstant;
use App\Constants\InvoiceConstant;
use App\Helpers\ArrayHelper;
use App\Helpers\BladeHelper;
use App\Helpers\ControllerHelper;
use App\IncomeExpenseType;
use App\Models\Customer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\CustomerInvoice;
use App\Services\CustomerInvoiceBalanceService;
use App\Support\AmountInWordsFormatter;
use App\Models\CustomerInvoiceItem;
use Illuminate\Http\Request;
use App\Models\InventoryType;
use App\Models\Inventory;
use App\Branch;
use App\Support\CustomerInvoiceFinancialCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
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
            $balanceService = app(CustomerInvoiceBalanceService::class);
            foreach ($customerInvoices as $invoice) {
                $invoice->total_amount = $invoice->total_due !== null
                    ? $invoice->total_due
                    : $invoice->items->sum('amount');

                try {
                    $invoice->current_balance_cents = $balanceService->balanceCentsFor($invoice);
                } catch (ValidationException $e) {
                    $invoice->current_balance_cents = null;
                }
            }

            return view('pages.customers.invoices.invoice-view', [
                'title' => 'Customer Invoices',
                'customerInvoices' => $customerInvoices,
                'resource' => 'Customer Invoices',
                'type' => 'list',
                'currency' => config('settings.currency_symbol', config('settings.currency_code', '')),
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

        $vat_percentage = app(\App\Support\VatConfiguration::class)
            ->validatePercentage(Config::get('settings.vat_percentage'));
        $discountConfiguration = app(\App\Support\InvoiceDiscountConfiguration::class);
        $discount_enabled = $discountConfiguration->enabledFromValue(Config::get('settings.discount_enabled', false));
        $discount_percentage = $discount_enabled
            ? $discountConfiguration->percentageFromValue(Config::get('settings.discount_percentage', 0))
            : null;

        return ControllerHelper::view([
            'title' => 'Add Invoice',
            'resource' => 'Customers',
            'type' => 'form',
            'action' => $this->route . '.store',
            'customers' => $customers,
            'customer_id_options' => BladeHelper::ddl_f($customers),
            'next_invoice_number' => $nextInvoiceNumber,
            'vat_percentage' => $vat_percentage,
            'discount_enabled' => $discount_enabled,
            'discount_percentage' => $discount_percentage,
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

        $discountEnabled = app(\App\Support\InvoiceDiscountConfiguration::class)
            ->enabledFromValue(Config::get('settings.discount_enabled', false));
        $discountPercentage = $discountEnabled
            ? app(\App\Support\InvoiceDiscountConfiguration::class)
                ->percentageFromValue(Config::get('settings.discount_percentage', 0))
            : null;

        $calculator = new CustomerInvoiceFinancialCalculator();
        $vatPercentage = app(\App\Support\VatConfiguration::class)
            ->validatePercentage(Config::get('settings.vat_percentage'));

        // Start a transaction
        DB::beginTransaction();

        try {
            // Aggregate duplicate lines before checking stock, then lock each
            // inventory row in a stable order to serialize concurrent sales.
            $requestedQuantities = [];
            foreach ($request->items as $item) {
                $itemId = (int) $item['item_id'];
                $requestedQuantities[$itemId] = ($requestedQuantities[$itemId] ?? 0) + (int) $item['quantity'];
            }
            ksort($requestedQuantities, SORT_NUMERIC);

            $inventoryItemsById = Inventory::whereIn('id', array_keys($requestedQuantities))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($requestedQuantities as $itemId => $requestedQuantity) {
                $inventoryItem = $inventoryItemsById->get($itemId);
                if (!$inventoryItem) {
                    throw ValidationException::withMessages([
                        'items' => ['A selected inventory item is no longer available.'],
                    ]);
                }

                if ((int) $inventoryItem->quantity_at_hand < $requestedQuantity) {
                    throw ValidationException::withMessages([
                        'items' => ['Insufficient stock for ' . ($inventoryItem->description ?: 'the selected item') . '.'],
                    ]);
                }
            }

            $calculatedLines = [];
            foreach ($request->items as $item) {
                $inventoryItem = $inventoryItemsById->get((int) $item['item_id']);

                if (!$calculator->submittedRateMatches($item['rate'], $inventoryItem->sale_price)) {
                    throw ValidationException::withMessages([
                        'items' => ['The selected item price has changed. Reload the invoice and try again.'],
                    ]);
                }

                $lineAmount = $calculator->lineAmount($item['quantity'], $inventoryItem->sale_price);
                $calculatedLines[] = [
                    'request' => $item,
                    'rate' => $inventoryItem->sale_price,
                    'amount' => $lineAmount,
                ];
            }

            $snapshot = $calculator->snapshot(
                array_column($calculatedLines, 'amount'),
                $vatPercentage,
                $discountEnabled,
                $discountPercentage
            );

            // Create a new invoice
            $invoice = new CustomerInvoice([
                'customer_id' => $request->customer_id,
                'invoice_date' => $request->invoice_date,
                'exchange_rate' => $request->exchange_rate,
            ] + $snapshot);

            $invoice->save();

            // Process invoice items and update inventory quantities
            $invoiceItems = [];
            foreach ($calculatedLines as $line) {
                $item = $line['request'];
                $invoiceItem = new CustomerInvoiceItem([
                    'invoice_id' => $invoice->id,
                    'item_id' => $item['item_id'],
                    'description' => $item['description'] ?? null,
                    'rate' => $line['rate'],
                    'quantity' => $item['quantity'],
                    'amount' => $line['amount'],
                ]);

                $invoiceItem->save();
                $invoiceItems[] = $invoiceItem;
            }

            // Deduct each item once using the combined quantity validated under
            // its row lock. The transaction protects both invoice and stock.
            foreach ($requestedQuantities as $itemId => $requestedQuantity) {
                $inventoryItem = $inventoryItemsById->get($itemId);
                $inventoryItem->update([
                    'quantity_at_hand' => (int) $inventoryItem->quantity_at_hand - $requestedQuantity,
                ]);
            }

            // Commit transaction
            DB::commit();

            // Prepare receipt data
            $customer = Customer::find($request->customer_id);

            // Calculate total amount for the invoice
            $totalAmount = $snapshot['subtotal'];

            // Get company settings
            $headerImage = Config::get('settings.header_image', null);
            $companyLogo = Config::get('settings.company_logo', 'upload/company-logo/e.png');
            $companyName = Config::get('settings.company_name', 'AYUGLOBAL');
            $companyColor = Config::get('settings.company_color', '#00897B');
            $address_1 = Config::get('settings.address_1', '');
            $address_2 = Config::get('settings.address_2', '');
            $city = Config::get('settings.city', '');
            $phone = Config::get('settings.phone', '');
            $email = Config::get('settings.email', '');
            $vat_percentage = $snapshot['vat_percentage'];

            return view('pages.customers.invoices.receipt', [
                'invoice' => $invoice,
                'customer' => $customer,
                'invoiceItems' => $invoiceItems,
                'totalAmount' => $totalAmount,
                'header_image' => $headerImage,
                'company_logo' => $companyLogo,
                'company_name' => $companyName,
                'contact_person' => Config::get('settings.contract_person', ''),
                'company_color' => $companyColor,
                'address_1' => $address_1,
                'address_2' => $address_2,
                'city' => $city,
                'state' => Config::get('settings.state', ''),
                'zip_code' => Config::get('settings.zip_code', ''),
                'phone' => $phone,
                'email' => $email,
                'vat_percentage' => $vat_percentage,
                'currency' => config('settings.currency_symbol', config('settings.currency_code', '')),
                'currency_code' => config('settings.currency_code', ''),
                'amount_in_words' => app(\App\Support\AmountInWordsFormatter::class)
                    ->format($invoice->balance_due ?? $invoice->total_due, config('settings.currency_code', '')),
                'invoice_creation_document' => true,
            ])->with('success', 'Invoice created successfully.');
        } catch (ValidationException $e) {
            DB::rollBack();

            throw $e;
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to create invoice. ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Generate an invoice PDF from the invoice's stored values, independent of payment history.
     */
    public function pdf($invoiceId)
    {
        $invoice = CustomerInvoice::with(['customer.branch', 'items.inventory.type'])->findOrFail($invoiceId);
        $totalAmount = $invoice->subtotal !== null
            ? $invoice->subtotal
            : $invoice->items->sum('amount');

        $invoiceTotal = $invoice->total_due ?? $totalAmount;
        $balanceDue = $invoice->balance_due ?? $invoiceTotal;
        $currencyCode = config('settings.currency_code', '');
        return Pdf::loadView('pages.customers.invoices.receipt', [
            'invoice' => $invoice,
            'customer' => $invoice->customer,
            'invoiceItems' => $invoice->items,
            'totalAmount' => $totalAmount,
            'header_image' => config('settings.header_image'),
            'company_logo' => config('settings.company_logo', 'upload/company-logo/e.png'),
            'company_name' => config('settings.company_name', 'AYUGLOBAL'),
            'contact_person' => config('settings.contract_person', ''),
            'company_color' => config('settings.company_color', '#00897B'),
            'address_1' => config('settings.address_1', ''),
            'address_2' => config('settings.address_2', ''),
            'city' => config('settings.city', ''),
            'state' => config('settings.state', ''),
            'zip_code' => config('settings.zip_code', ''),
            'phone' => config('settings.phone', ''),
            'email' => config('settings.email', ''),
            'vat_percentage' => $invoice->vat_percentage,
            'currency' => config('settings.currency_symbol', $currencyCode),
            'currency_code' => $currencyCode,
            'amount_in_words' => app(AmountInWordsFormatter::class)->format($balanceDue, $currencyCode),
        ])->setPaper('A4')->stream('invoice_' . $invoice->id . '.pdf');
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
