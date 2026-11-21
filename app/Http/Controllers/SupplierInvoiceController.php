<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\SupplierInvoice;
use App\Services\SupplierInvoiceService;
use App\Support\AmountInWordsFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class SupplierInvoiceController extends Controller
{
    private $invoices;

    public function __construct(SupplierInvoiceService $invoices)
    {
        $this->middleware('auth');
        $this->invoices = $invoices;
    }

    public function index(Request $request)
    {
        $this->authorizePermission('Show');
        $query = SupplierInvoice::with(['purchaseOrder', 'vendor', 'branch'])
            ->orderByDesc('invoice_date')->orderByDesc('id');
        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($builder) use ($term) {
                $builder->where('internal_invoice_number', 'like', '%' . $term . '%')
                    ->orWhere('supplier_invoice_number', 'like', '%' . $term . '%')
                    ->orWhere('vendor_name_snapshot', 'like', '%' . $term . '%');
            });
        }
        if (in_array($request->input('status'), [SupplierInvoice::STATUS_DRAFT, SupplierInvoice::STATUS_REVIEW_REQUIRED, SupplierInvoice::STATUS_APPROVED, SupplierInvoice::STATUS_CANCELLED], true)) {
            $query->where('status', $request->input('status'));
        }
        return view('admin.supplier-invoices.index', ['invoices' => $query->paginate(20)->withQueryString()]);
    }

    public function create($purchaseOrderId)
    {
        $this->authorizePermission('Create');
        $order = PurchaseOrder::with(['items.inventoryItem', 'vendor', 'branch'])->findOrFail($purchaseOrderId);
        $lines = $this->invoices->formLines($order);
        $submissionToken = (string) Str::uuid();
        $invoice = null;
        return view('admin.supplier-invoices.form', compact('order', 'lines', 'submissionToken', 'invoice'));
    }

    public function store(Request $request)
    {
        $this->authorizePermission('Create');
        $data = $this->validated($request, true);
        $invoice = $this->invoices->saveDraft($request->user(), $data);
        return redirect()->route('supplier-invoices.show', $invoice)->with('success', __('supplier_invoices.messages.saved'));
    }

    public function edit($id)
    {
        $this->authorizePermission('Edit');
        $invoice = SupplierInvoice::with('items')->findOrFail($id);
        abort_unless(in_array($invoice->status, [SupplierInvoice::STATUS_DRAFT, SupplierInvoice::STATUS_REVIEW_REQUIRED], true), 403);
        $order = PurchaseOrder::with(['items.inventoryItem', 'vendor', 'branch'])->findOrFail($invoice->purchase_order_id);
        $existing = $invoice->items->keyBy('purchase_order_item_id');
        $lines = collect($this->invoices->formLines($order, $invoice->id))->map(function ($line) use ($existing) {
            $prior = $existing->get($line->id);
            if ($prior) {
                $line->quantity = $prior->quantity;
                $line->supplier_unit_price = $prior->supplier_unit_price;
            }
            return $line;
        });
        $submissionToken = $invoice->submission_token;
        return view('admin.supplier-invoices.form', compact('order', 'lines', 'submissionToken', 'invoice'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizePermission('Edit');
        $data = $this->validated($request, false);
        $invoice = $this->invoices->saveDraft($request->user(), $data, (int) $id);
        return redirect()->route('supplier-invoices.show', $invoice)->with('success', __('supplier_invoices.messages.saved'));
    }

    public function show($id)
    {
        $this->authorizePermission('Show');
        $invoice = SupplierInvoice::with([
            'purchaseOrder', 'vendor', 'branch', 'creator', 'approver', 'canceller',
            'items.purchaseOrderItem', 'items.receiptAllocations.goodsReceivedItem.goodsReceipt',
            'payments.paymentMode', 'payments.creator',
        ])->findOrFail($id);
        return view('admin.supplier-invoices.show', compact('invoice'));
    }

    public function pdf($id)
    {
        $this->authorizePermission('Pdf');
        $invoice = SupplierInvoice::with(['purchaseOrder', 'vendor', 'branch', 'items'])->findOrFail($id);
        $settings = config('settings', []);

        return Pdf::loadView('admin.supplier-invoices.pdf', [
            'invoice' => $invoice,
            'vendor' => $invoice->vendor,
            'purchaseOrder' => $invoice->purchaseOrder,
            'amountInWords' => $invoice->total_amount === null
                ? ''
                : app(AmountInWordsFormatter::class)->format($invoice->total_amount, $invoice->currency_code),
            'company_name' => $settings['company_name'] ?? 'AYUGLOBAL',
            'contact_person' => $settings['contract_person'] ?? '',
            'company_logo' => $settings['company_logo'] ?? null,
            'header_image' => $settings['header_image'] ?? null,
            'company_color' => $settings['company_color'] ?? '#176b63',
            'address_1' => $settings['address_1'] ?? '',
            'address_2' => $settings['address_2'] ?? '',
            'city' => $settings['city'] ?? '',
            'state' => $settings['state'] ?? '',
            'zip_code' => $settings['zip_code'] ?? '',
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
        ])->setPaper('A4')->stream($invoice->internal_invoice_number . '.pdf');
    }
    public function approve(Request $request, $id)
    {
        // The existing Role Management Edit permission is the module's approval authority.
        $this->authorizePermission('Edit');
        $invoice = $this->invoices->approve((int) $id, $request->user());
        if ($invoice->status === SupplierInvoice::STATUS_REVIEW_REQUIRED) {
            return redirect()->route('supplier-invoices.show', $invoice)->with('warning', $invoice->review_reason);
        }
        return redirect()->route('supplier-invoices.show', $invoice)->with('success', __('supplier_invoices.messages.approved'));
    }

    public function cancel(Request $request, $id)
    {
        $this->authorizePermission('Delete');
        $data = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string|min:3|max:2000',
        ])->validate();
        $invoice = $this->invoices->cancel((int) $id, $request->user(), $data['cancellation_reason']);
        return redirect()->route('supplier-invoices.show', $invoice)->with('success', __('supplier_invoices.messages.cancelled'));
    }

    private function validated(Request $request, bool $creating): array
    {
        $rules = [
            'vendor_id' => 'sometimes|integer',
            'branch_id' => 'sometimes|integer',
            'currency_code' => 'sometimes|string|size:3',
            'supplier_invoice_number' => 'required|string|max:120',
            'invoice_date' => 'required|date_format:Y-m-d',
            'due_date' => 'nullable|date_format:Y-m-d|after_or_equal:invoice_date',
            'items' => 'required|array|min:1|max:100',
            'items.*.quantity' => ['nullable', 'regex:/^\d+(?:\.\d{1,3})?$/'],
            'items.*.supplier_unit_price' => ['nullable', 'regex:/^\d+(?:\.\d{1,2})?$/'],
        ];
        if ($creating) {
            $rules['purchase_order_id'] = 'required|integer|exists:purchase_orders,id';
            $rules['submission_token'] = 'required|uuid';
        }
        return Validator::make($request->all(), $rules)->validate();
    }

    private function authorizePermission(string $action): void
    {
        abort_unless((bool) config('role_manage.SupplierInvoice.' . $action), 403);
    }
}
