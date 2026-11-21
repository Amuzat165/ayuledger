<?php

namespace App\Http\Controllers;

use App\Branch;
use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use App\Services\PurchaseOrderNumberService;
use App\Support\PurchaseOrderCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    private $calculator;
    private $numberService;

    public function __construct(PurchaseOrderCalculator $calculator, PurchaseOrderNumberService $numberService)
    {
        $this->middleware('auth');
        $this->calculator = $calculator;
        $this->numberService = $numberService;
    }

    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['vendor', 'branch'])->orderByDesc('order_date')->orderByDesc('id');
        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->where(function ($builder) use ($term) {
                $builder->where('purchase_order_number', 'like', '%' . $term . '%')
                    ->orWhere('vendor_name_snapshot', 'like', '%' . $term . '%')
                    ->orWhere('branch_name_snapshot', 'like', '%' . $term . '%');
            });
        }
        if ($request->filled('vendor_id') && ctype_digit((string) $request->input('vendor_id'))) {
            $query->where('vendor_id', $request->input('vendor_id'));
        }
        if ($request->filled('branch_id') && ctype_digit((string) $request->input('branch_id'))) {
            $query->where('branch_id', $request->input('branch_id'));
        }
        if (in_array($request->input('status'), PurchaseOrder::statuses(), true)) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('from') || $request->filled('to')) {
            $dates = Validator::make($request->only('from', 'to'), [
                'from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from',
            ])->validate();
            $query->whereBetween('order_date', [$dates['from'], $dates['to']]);
        }

        return view('admin.purchase-orders.index', $this->pageData([
            'orders' => $query->paginate(20)->withQueryString(),
            'vendors' => Vendor::orderBy('name')->get(['id', 'name']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
        ]));
    }

    public function create()
    {
        return view('admin.purchase-orders.form', $this->formData(new PurchaseOrder([
            'order_date' => now()->toDateString(),
        ]), []));
    }

    public function store(Request $request)
    {
        $data = $this->validatedDraft($request);
        $vendor = Vendor::whereKey($data['vendor_id'])->firstOrFail();
        $branch = Branch::whereKey($data['branch_id'])->firstOrFail();
        $lines = $this->prepareLines($data['items']);
        $totals = $this->calculator->calculate($lines);
        $token = $data['submission_token'];

        $order = DB::transaction(function () use ($data, $vendor, $branch, $lines, $totals, $token, $request) {
            $existing = PurchaseOrder::where('submission_token', $token)->first();
            if ($existing) {
                return $existing;
            }

            $order = PurchaseOrder::create([
                'purchase_order_number' => $this->numberService->next(),
                'submission_token' => $token,
                'vendor_id' => $vendor->id,
                'vendor_name_snapshot' => $vendor->name,
                'vendor_address_snapshot' => $vendor->address,
                'vendor_phone_snapshot' => $vendor->phone_number,
                'vendor_account_snapshot' => $vendor->account,
                'vendor_terms_snapshot' => $vendor->terms_of_payment,
                'vendor_branch_name_snapshot' => optional($vendor->branch)->name,
                'branch_id' => $branch->id,
                'branch_name_snapshot' => $branch->name,
                'currency_code' => config('settings.currency_code', 'NGN'),
                'currency_symbol' => config('settings.currency_symbol', config('settings.currency_code', 'NGN')),
                'currency_position' => config('settings.currency_position', 'Prefix'),
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?: null,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'subtotal' => $totals['subtotal'],
                'order_total' => $totals['order_total'],
                'delivery_instructions' => $data['delivery_instructions'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
            $this->persistLines($order, $lines, $totals['lines'], $request->user()->id);
            return $order;
        });

        return redirect()->route('purchase-orders.show', $order)->with('success', __('purchase_orders.messages.created'));
    }

    public function show($id)
    {
        $order = PurchaseOrder::with(['vendor', 'branch', 'items.inventoryItem', 'creator', 'updater', 'approver', 'goodsReceipts', 'supplierInvoices'])->findOrFail($id);
        return view('admin.purchase-orders.show', $this->pageData(['order' => $order]));
    }

    public function approve(Request $request, $id)
    {
        abort_unless(config('role_manage.PurchaseOrder.Edit'), 403);

        $order = DB::transaction(function () use ($id, $request) {
            $order = PurchaseOrder::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($order->status === PurchaseOrder::STATUS_DRAFT, 422, __('goods_received.errors.only_draft_can_be_approved'));
            abort_unless($order->items()->exists(), 422, __('goods_received.errors.order_requires_items'));
            $order->status = PurchaseOrder::STATUS_APPROVED;
            $order->approved_by = $request->user()->id;
            $order->approved_at = now();
            $order->updated_by = $request->user()->id;
            $order->save();
            return $order;
        });

        return redirect()->route('purchase-orders.show', $order)->with('success', __('goods_received.messages.purchase_order_approved'));
    }

    public function edit($id)
    {
        $order = PurchaseOrder::with(['vendor', 'branch', 'items.inventoryItem'])->findOrFail($id);
        abort_unless($order->status === PurchaseOrder::STATUS_DRAFT, 403);
        return view('admin.purchase-orders.form', $this->formData($order, $order->items->all()));
    }

    public function update(Request $request, $id)
    {
        $data = $this->validatedDraft($request, true);
        $vendor = Vendor::whereKey($data['vendor_id'])->firstOrFail();
        $branch = Branch::whereKey($data['branch_id'])->firstOrFail();
        $existingOrder = PurchaseOrder::with('items')->findOrFail($id);
        abort_unless($existingOrder->status === PurchaseOrder::STATUS_DRAFT, 403);
        $lines = $this->prepareLines($data['items'], $existingOrder->items);
        $totals = $this->calculator->calculate($lines);

        $order = DB::transaction(function () use ($id, $data, $vendor, $branch, $lines, $totals, $request) {
            $order = PurchaseOrder::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($order->status === PurchaseOrder::STATUS_DRAFT, 403);
            $order->fill([
                'vendor_id' => $vendor->id,
                'vendor_name_snapshot' => $vendor->name,
                'vendor_address_snapshot' => $vendor->address,
                'vendor_phone_snapshot' => $vendor->phone_number,
                'vendor_account_snapshot' => $vendor->account,
                'vendor_terms_snapshot' => $vendor->terms_of_payment,
                'vendor_branch_name_snapshot' => optional($vendor->branch)->name,
                'branch_id' => $branch->id,
                'branch_name_snapshot' => $branch->name,
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?: null,
                'subtotal' => $totals['subtotal'],
                'order_total' => $totals['order_total'],
                'delivery_instructions' => $data['delivery_instructions'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'updated_by' => $request->user()->id,
            ])->save();
            $order->items()->delete();
            $this->persistLines($order, $lines, $totals['lines'], $request->user()->id);
            return $order;
        });

        return redirect()->route('purchase-orders.show', $order)->with('success', __('purchase_orders.messages.updated'));
    }

    public function vendorDetails($id)
    {
        $vendor = Vendor::with('branch')->whereKey($id)->firstOrFail();
        return response()->json([
            'name' => $vendor->name, 'address' => $vendor->address, 'phone_number' => $vendor->phone_number,
            'account' => $vendor->account, 'terms_of_payment' => $vendor->terms_of_payment,
            'branch' => optional($vendor->branch)->name,
        ]);
    }

    public function inventoryDetails($id)
    {
        $item = Inventory::whereKey($id)->firstOrFail();
        return response()->json([
            'name' => trim($item->description . ' ' . ($item->sub_item ?: '')),
            'description' => trim($item->description . ' ' . ($item->sub_item ?: '')),
            'unit' => $item->unit_of_measurement,
            'reference_unit_price' => $item->unit_cost,
        ]);
    }

    private function validatedDraft(Request $request, bool $updating = false): array
    {
        $rules = [
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')->whereNull('deleted_at')],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->whereNull('deleted_at')],
            'order_date' => 'required|date_format:Y-m-d',
            'expected_delivery_date' => 'nullable|date_format:Y-m-d|after_or_equal:order_date',
            'delivery_instructions' => 'nullable|string|max:5000',
            'internal_notes' => 'nullable|string|max:5000',
            'items' => 'required|array|min:1|max:100',
            'items.*.inventory_item_id' => ['nullable', 'integer', Rule::exists('inventories', 'id')->whereNull('deleted_at')],
            'items.*.description' => 'nullable|string|max:1000',
            'items.*.unit' => 'nullable|string|max:64',
            'items.*.quantity' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/'],
            'items.*.agreed_unit_price' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
        ];
        if (!$updating) {
            $rules['submission_token'] = 'required|uuid';
        }
        $data = Validator::make($request->all(), $rules)->validate();
        foreach ($data['items'] as $index => $line) {
            if (empty($line['inventory_item_id']) && trim((string) ($line['description'] ?? '')) === '') {
                throw ValidationException::withMessages(["items.$index.description" => [__('purchase_orders.validation.item_description_required')]]);
            }
            if (trim((string) ($line['unit'] ?? '')) === '') {
                throw ValidationException::withMessages(["items.$index.unit" => [__('purchase_orders.validation.unit_required')]]);
            }
        }
        return $data;
    }

    private function prepareLines(array $lines, $existingLines = null): array
    {
        $usedExistingLines = [];

        return array_map(function (array $line) use ($existingLines, &$usedExistingLines) {
            $inventory = !empty($line['inventory_item_id']) ? Inventory::whereKey($line['inventory_item_id'])->firstOrFail() : null;
            $classification = !$inventory
                ? PurchaseOrderItem::CLASSIFICATION_SERVICE
                : ($inventory->track_stock === null
                    ? PurchaseOrderItem::CLASSIFICATION_UNCLASSIFIED
                    : ($inventory->tracksStock()
                        ? PurchaseOrderItem::CLASSIFICATION_STOCK
                        : PurchaseOrderItem::CLASSIFICATION_SERVICE));

            // Editing a draft preserves the classification of a matching existing line.
            // New/replaced lines derive their classification from the server-side Inventory master.
            if ($existingLines) {
                foreach ($existingLines as $existingLine) {
                    if (isset($usedExistingLines[$existingLine->id])) {
                        continue;
                    }

                    $sameInventory = $inventory && $existingLine->inventory_item_id
                        && (int) $existingLine->inventory_item_id === (int) $inventory->id;
                    $sameCustomLine = !$inventory && !$existingLine->inventory_item_id
                        && trim((string) $existingLine->description) === trim((string) ($line['description'] ?? ''))
                        && trim((string) $existingLine->unit) === trim((string) ($line['unit'] ?? ''));
                    if ($sameInventory || $sameCustomLine) {
                        $classification = $existingLine->inventory_classification;
                        $usedExistingLines[$existingLine->id] = true;
                        break;
                    }
                }
            }

            return [
                'inventory_item_id' => $inventory ? $inventory->id : null,
                'inventory_classification' => $classification,
                'inventory_item_name_snapshot' => $inventory ? trim($inventory->description . ' ' . ($inventory->sub_item ?: '')) : trim($line['description']),
                'description' => trim((string) (($line['description'] ?? '') ?: ($inventory ? $inventory->description : ''))),
                'unit' => trim((string) (($line['unit'] ?? '') ?: ($inventory ? $inventory->unit_of_measurement : ''))),
                'reference_unit_price' => $inventory ? $inventory->unit_cost : null,
                'quantity' => $line['quantity'],
                'agreed_unit_price' => $line['agreed_unit_price'],
            ];
        }, $lines);
    }

    private function persistLines(PurchaseOrder $order, array $lines, array $calculated, int $userId): void
    {
        foreach ($lines as $index => $line) {
            PurchaseOrderItem::create(array_merge($line, [
                'purchase_order_id' => $order->id,
                'quantity' => $calculated[$index]['quantity'],
                'agreed_unit_price' => $calculated[$index]['agreed_unit_price'],
                'line_total' => $calculated[$index]['line_total'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]));
        }
    }

    private function formData(PurchaseOrder $order, array $lines): array
    {
        $data = $this->pageData([
            'order' => $order,
            'vendors' => Vendor::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'inventoryItems' => Inventory::orderBy('description')->get(),
            'lines' => $lines,
            'submissionToken' => old('submission_token', (string) Str::uuid()),
        ]);
        if ($order->exists) {
            $data['currencyCode'] = $order->currency_code;
            $data['currencySymbol'] = $order->currency_symbol;
            $data['currencyPosition'] = $order->currency_position;
        }
        return $data;
    }

    private function pageData(array $data): array
    {
        $data['currencyCode'] = config('settings.currency_code', 'NGN');
        $data['currencySymbol'] = config('settings.currency_symbol', $data['currencyCode']);
        $data['currencyPosition'] = config('settings.currency_position', 'Prefix');
        return $data;
    }
}
