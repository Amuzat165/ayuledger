<?php

namespace App\Services;

use App\Models\GoodsReceivedItem;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceItem;
use App\Models\SupplierInvoiceReceiptAllocation;
use App\Support\SupplierInvoiceCalculator;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupplierInvoiceService
{
    private $calculator;
    private $numberService;

    public function __construct(SupplierInvoiceCalculator $calculator, SupplierInvoiceNumberService $numberService)
    {
        $this->calculator = $calculator;
        $this->numberService = $numberService;
    }

    public function formLines(PurchaseOrder $order, ?int $excludeInvoiceId = null): array
    {
        $this->assertOrderCanBeBilled($order);
        $lines = [];
        foreach ($order->items as $line) {
            $classification = $line->inventory_classification;
            $trackStock = $classification === PurchaseOrderItem::CLASSIFICATION_STOCK
                ? true
                : ($classification === PurchaseOrderItem::CLASSIFICATION_SERVICE ? false : null);
            $remainingMilli = $classification === PurchaseOrderItem::CLASSIFICATION_STOCK
                ? $this->stockRemainingMilli($order, $line, $excludeInvoiceId, false)
                : ($classification === PurchaseOrderItem::CLASSIFICATION_SERVICE
                    ? $this->serviceRemainingMilli($line, $excludeInvoiceId)
                    : 0);
            $lines[] = (object) [
                'id' => $line->id,
                'description' => $line->inventory_item_name_snapshot ?: $line->description,
                'unit' => $line->unit,
                'purchase_order_unit_price' => $line->agreed_unit_price,
                'ordered_quantity' => $line->quantity,
                'remaining_quantity' => $this->fromMilli($remainingMilli),
                'track_stock' => $trackStock,
            ];
        }
        return $lines;
    }

    public function saveDraft(User $user, array $data, ?int $invoiceId = null): SupplierInvoice
    {
        $initialInvoice = $invoiceId ? SupplierInvoice::findOrFail($invoiceId) : null;
        $purchaseOrderId = $invoiceId ? $initialInvoice->purchase_order_id : (int) $data['purchase_order_id'];

        return DB::transaction(function () use ($user, $data, $invoiceId, $initialInvoice, $purchaseOrderId) {
            $order = PurchaseOrder::whereKey($purchaseOrderId)->lockForUpdate()->firstOrFail();
            $this->assertOrderCanBeBilled($order);
            if (isset($data['vendor_id']) && (int) $data['vendor_id'] !== (int) $order->vendor_id) {
                throw ValidationException::withMessages(['vendor_id' => [__('supplier_invoices.errors.inherited_supplier_mismatch')]]);
            }
            if (array_key_exists('branch_id', $data) && (string) $data['branch_id'] !== (string) $order->branch_id) {
                throw ValidationException::withMessages(['branch_id' => [__('supplier_invoices.errors.inherited_branch_mismatch')]]);
            }
            if (isset($data['currency_code']) && strtoupper((string) $data['currency_code']) !== strtoupper((string) $order->currency_code)) {
                throw ValidationException::withMessages(['currency_code' => [__('supplier_invoices.errors.currency_mismatch')]]);
            }

            if ($invoiceId) {
                $invoice = SupplierInvoice::whereKey($invoiceId)->lockForUpdate()->firstOrFail();
                if (!in_array($invoice->status, [SupplierInvoice::STATUS_DRAFT, SupplierInvoice::STATUS_REVIEW_REQUIRED], true)) {
                    throw ValidationException::withMessages(['invoice' => [__('supplier_invoices.errors.only_unapproved_editable')]]);
                }
                $invoice->items()->get()->each(function (SupplierInvoiceItem $item) {
                    $item->receiptAllocations()->delete();
                    $item->delete();
                });
            } else {
                $existing = SupplierInvoice::where('submission_token', $data['submission_token'])->lockForUpdate()->first();
                if ($existing) {
                    if ((int) $existing->purchase_order_id !== (int) $order->id) {
                        throw ValidationException::withMessages(['submission_token' => [__('supplier_invoices.errors.invalid_submission')]]);
                    }
                    return $existing;
                }
                $invoice = new SupplierInvoice();
                $invoice->internal_invoice_number = $this->numberService->next();
                $invoice->submission_token = $data['submission_token'];
                $invoice->created_by = $user->id;
            }

            $supplierNumber = trim((string) $data['supplier_invoice_number']);
            $duplicate = SupplierInvoice::where('vendor_id', $order->vendor_id)
                ->where('supplier_invoice_number', $supplierNumber)
                ->when($invoiceId, function ($query) use ($invoiceId) { $query->where('id', '<>', $invoiceId); })
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['supplier_invoice_number' => [__('supplier_invoices.errors.duplicate_supplier_number')]]);
            }

            $orderItems = $order->items()->lockForUpdate()->get()->keyBy('id');
            $prepared = $this->prepareLines($order, $orderItems, $data['items'] ?? []);
            $totals = $this->calculator->calculate($prepared['lines'], config('settings', []));
            $reviewReasons = [];
            if ($totals['price_variance']) {
                $reviewReasons[] = __('supplier_invoices.price_variance_review');
            }
            if ($totals['wht_error']) {
                $reviewReasons[] = $totals['wht_error'];
            }

            if (!$invoiceId) {
                $invoice->vendor_id = $order->vendor_id;
                $invoice->purchase_order_id = $order->id;
            }
            $invoice->fill([
                'vendor_id' => $order->vendor_id,
                'purchase_order_id' => $order->id,
                'branch_id' => $order->branch_id,
                'vendor_name_snapshot' => $order->vendor_name_snapshot,
                'branch_name_snapshot' => $order->branch_name_snapshot,
                'currency_code' => $order->currency_code,
                'currency_symbol' => $order->currency_symbol,
                'currency_position' => $order->currency_position,
                'supplier_invoice_number' => $supplierNumber,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?: null,
                'status' => $reviewReasons ? SupplierInvoice::STATUS_REVIEW_REQUIRED : SupplierInvoice::STATUS_DRAFT,
                'review_reason' => $reviewReasons ? implode(' ', $reviewReasons) : null,
                'subtotal' => $totals['subtotal'],
                'wht_amount' => $totals['wht_amount'],
                'total_amount' => $totals['total_amount'],
                'amount_paid' => '0.00',
                'amount_remaining' => null,
                'wht_enabled_snapshot' => $totals['wht_enabled_snapshot'],
                'wht_mode_snapshot' => $totals['wht_mode_snapshot'],
                'wht_rate_snapshot' => $totals['wht_rate_snapshot'],
                'wht_base_snapshot' => $totals['wht_base_snapshot'],
                'wht_calculation_version' => $totals['wht_calculation_version'],
                'updated_by' => $user->id,
            ]);
            $invoice->save();

            foreach ($totals['lines'] as $index => $calculated) {
                $source = $prepared['lines'][$index];
                $invoiceItem = SupplierInvoiceItem::create([
                    'supplier_invoice_id' => $invoice->id,
                    'purchase_order_item_id' => $source['purchase_order_item_id'],
                    'inventory_id' => $source['inventory_id'],
                    'description_snapshot' => $source['description_snapshot'],
                    'unit_snapshot' => $source['unit_snapshot'],
                    'track_stock_snapshot' => $source['track_stock_snapshot'],
                    'quantity' => $calculated['quantity'],
                    'purchase_order_unit_price' => $calculated['purchase_order_unit_price'],
                    'supplier_unit_price' => $calculated['supplier_unit_price'],
                    'line_total' => $calculated['line_total'],
                ]);
                foreach ($prepared['allocations'][$index] ?? [] as $allocation) {
                    SupplierInvoiceReceiptAllocation::create([
                        'supplier_invoice_item_id' => $invoiceItem->id,
                        'goods_received_item_id' => $allocation['goods_received_item_id'],
                        'allocated_quantity' => $allocation['allocated_quantity'],
                    ]);
                }
            }

            return $invoice->load(['items.receiptAllocations', 'purchaseOrder']);
        });
    }

    public function approve(int $invoiceId, User $user): SupplierInvoice
    {
        $initial = SupplierInvoice::findOrFail($invoiceId);
        return DB::transaction(function () use ($initial, $user) {
            PurchaseOrder::whereKey($initial->purchase_order_id)->lockForUpdate()->firstOrFail();
            $invoice = SupplierInvoice::whereKey($initial->id)->lockForUpdate()->with('items')->firstOrFail();
            if ($invoice->status === SupplierInvoice::STATUS_APPROVED) {
                return $invoice;
            }
            if (!in_array($invoice->status, [SupplierInvoice::STATUS_DRAFT, SupplierInvoice::STATUS_REVIEW_REQUIRED], true)) {
                throw ValidationException::withMessages(['invoice' => [__('supplier_invoices.errors.not_approvable')]]);
            }

            $lines = $invoice->items->map(function ($item) {
                return [
                    'purchase_order_unit_price' => $item->purchase_order_unit_price,
                    'supplier_unit_price' => $item->supplier_unit_price,
                    'quantity' => $item->quantity,
                ];
            })->all();
            $totals = $this->calculator->calculate($lines, config('settings', []));
            if ($totals['wht_error']) {
                $invoice->status = SupplierInvoice::STATUS_REVIEW_REQUIRED;
                $invoice->review_reason = $totals['wht_error'];
                $invoice->wht_amount = null;
                $invoice->total_amount = null;
                $invoice->amount_remaining = null;
                $invoice->wht_enabled_snapshot = null;
                $invoice->wht_mode_snapshot = $totals['wht_mode_snapshot'];
                $invoice->wht_rate_snapshot = null;
                $invoice->wht_base_snapshot = null;
                $invoice->wht_calculation_version = null;
                $invoice->updated_by = $user->id;
                $invoice->save();
                return $invoice;
            }

            $invoice->fill([
                'status' => SupplierInvoice::STATUS_APPROVED,
                'review_reason' => null,
                'subtotal' => $totals['subtotal'],
                'wht_amount' => $totals['wht_amount'],
                'total_amount' => $totals['total_amount'],
                'amount_paid' => '0.00',
                'amount_remaining' => $totals['total_amount'],
                'wht_enabled_snapshot' => $totals['wht_enabled_snapshot'],
                'wht_mode_snapshot' => $totals['wht_mode_snapshot'],
                'wht_rate_snapshot' => $totals['wht_rate_snapshot'],
                'wht_base_snapshot' => $totals['wht_base_snapshot'],
                'wht_calculation_version' => $totals['wht_calculation_version'],
                'approved_by' => $user->id,
                'approved_at' => now(),
                'updated_by' => $user->id,
            ]);
            $invoice->save();
            return $invoice;
        });
    }

    public function cancel(int $invoiceId, User $user, string $reason): SupplierInvoice
    {
        $initial = SupplierInvoice::findOrFail($invoiceId);
        return DB::transaction(function () use ($initial, $user, $reason) {
            PurchaseOrder::whereKey($initial->purchase_order_id)->lockForUpdate()->firstOrFail();
            $invoice = SupplierInvoice::whereKey($initial->id)->lockForUpdate()->firstOrFail();
            if ($invoice->status === SupplierInvoice::STATUS_CANCELLED) {
                return $invoice;
            }
            if (!in_array($invoice->status, [SupplierInvoice::STATUS_DRAFT, SupplierInvoice::STATUS_REVIEW_REQUIRED, SupplierInvoice::STATUS_APPROVED], true)) {
                throw ValidationException::withMessages(['invoice' => [__('supplier_invoices.errors.not_cancellable')]]);
            }
            if ($invoice->status === SupplierInvoice::STATUS_APPROVED && (float) $invoice->amount_paid > 0) {
                throw ValidationException::withMessages(['invoice' => [__('supplier_invoices.errors.paid_cannot_cancel')]]);
            }
            $invoice->fill([
                'status' => SupplierInvoice::STATUS_CANCELLED,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'cancellation_reason' => trim($reason),
                'updated_by' => $user->id,
            ]);
            $invoice->save();
            return $invoice;
        });
    }

    private function prepareLines(PurchaseOrder $order, $orderItems, array $submitted): array
    {
        $prepared = [];
        $allocations = [];
        foreach ($submitted as $itemId => $values) {
            if (!is_array($values)) {
                throw ValidationException::withMessages(['items' => [__('supplier_invoices.errors.invalid_line')]]);
            }
            if (!ctype_digit((string) $itemId) || !$orderItems->has((int) $itemId)) {
                throw ValidationException::withMessages(['items' => [__('supplier_invoices.errors.line_not_in_order')]]);
            }
            $quantity = $values['quantity'] ?? null;
            if ($quantity === null || trim((string) $quantity) === '') {
                continue;
            }
            $line = $orderItems->get((int) $itemId);
            $quantityMilli = $this->toMilli($quantity);
            $classification = $line->inventory_classification;
            if (!in_array($classification, [
                PurchaseOrderItem::CLASSIFICATION_STOCK,
                PurchaseOrderItem::CLASSIFICATION_SERVICE,
            ], true)) {
                throw ValidationException::withMessages(['items.' . $itemId . '.quantity' => [__('supplier_invoices.errors.unclassified_inventory')]]);
            }

            if ($classification === PurchaseOrderItem::CLASSIFICATION_STOCK) {
                $buckets = $this->availableReceiptBuckets($order, $line, true);
                $available = array_sum(array_column($buckets, 'available_milli'));
                if ($quantityMilli > $available) {
                    throw ValidationException::withMessages(['items.' . $itemId . '.quantity' => [__('supplier_invoices.errors.exceeds_received')]]);
                }
                $remaining = $quantityMilli;
                foreach ($buckets as $bucket) {
                    if ($remaining <= 0) { break; }
                    $take = min($remaining, $bucket['available_milli']);
                    if ($take > 0) {
                        $allocations[count($prepared)][] = [
                            'goods_received_item_id' => $bucket['id'],
                            'allocated_quantity' => $this->fromMilli($take),
                        ];
                        $remaining -= $take;
                    }
                }
            } else {
                $remaining = $this->serviceRemainingMilli($line, null);
                if ($quantityMilli > $remaining) {
                    throw ValidationException::withMessages(['items.' . $itemId . '.quantity' => [__('supplier_invoices.errors.exceeds_ordered')]]);
                }
            }

            $supplierPrice = $values['supplier_unit_price'] ?? null;
            if (!is_string($supplierPrice) && !is_numeric($supplierPrice)) {
                throw ValidationException::withMessages(['items.' . $itemId . '.supplier_unit_price' => [__('supplier_invoices.errors.invalid_price')]]);
            }
            $prepared[] = [
                'purchase_order_item_id' => $line->id,
                'inventory_id' => $line->inventory_item_id,
                'description_snapshot' => $line->inventory_item_name_snapshot ?: $line->description,
                'unit_snapshot' => $line->unit,
                'track_stock_snapshot' => $classification === PurchaseOrderItem::CLASSIFICATION_STOCK
                    ? true
                    : ($classification === PurchaseOrderItem::CLASSIFICATION_SERVICE ? false : null),
                'quantity' => $this->fromMilli($quantityMilli),
                'purchase_order_unit_price' => $line->agreed_unit_price,
                'supplier_unit_price' => (string) $supplierPrice,
            ];
        }
        if (!$prepared) {
            throw ValidationException::withMessages(['items' => [__('supplier_invoices.errors.add_line')]]);
        }
        return ['lines' => $prepared, 'allocations' => $allocations];
    }

    private function availableReceiptBuckets(PurchaseOrder $order, PurchaseOrderItem $line, bool $lock): array
    {
        $query = DB::table('goods_received_items as gri')
            ->join('goods_receipts as gr', 'gr.id', '=', 'gri.goods_receipt_id')
            ->where('gri.purchase_order_item_id', $line->id)
            ->where('gr.purchase_order_id', $order->id)
            ->where('gr.status', GoodsReceipt::STATUS_CONFIRMED)
            ->orderBy('gr.received_date')->orderBy('gr.id')->orderBy('gri.id');
        if ($lock) { $query->lockForUpdate(); }
        $rows = $query->get([
            'gri.id', 'gri.received_quantity', 'gr.purchase_order_id', 'gr.branch_id',
            'gri.inventory_item_id', 'gri.purchase_order_item_id',
        ]);
        $buckets = [];
        foreach ($rows as $row) {
            if ((int) $row->purchase_order_id !== (int) $order->id
                || (int) $row->branch_id !== (int) $order->branch_id
                || (int) $row->purchase_order_item_id !== (int) $line->id
                || ($row->inventory_item_id !== null && (int) $row->inventory_item_id !== (int) $line->inventory_item_id)) {
                throw ValidationException::withMessages(['items' => [__('supplier_invoices.errors.receipt_mismatch')]]);
            }
            $reserved = DB::table('supplier_invoice_receipt_allocations as a')
                ->join('supplier_invoice_items as sii', 'sii.id', '=', 'a.supplier_invoice_item_id')
                ->join('supplier_invoices as si', 'si.id', '=', 'sii.supplier_invoice_id')
                ->where('a.goods_received_item_id', $row->id)
                ->where('si.status', '<>', SupplierInvoice::STATUS_CANCELLED)
                ->sum('a.allocated_quantity');
            $available = max(0, $this->toMilli($row->received_quantity) - $this->toMilli($reserved ?: 0));
            if ($available > 0) {
                $buckets[] = ['id' => (int) $row->id, 'available_milli' => $available];
            }
        }
        return $buckets;
    }

    private function stockRemainingMilli(PurchaseOrder $order, PurchaseOrderItem $line, ?int $excludeInvoiceId, bool $lock): int
    {
        $buckets = $this->availableReceiptBuckets($order, $line, $lock);
        if ($excludeInvoiceId) {
            $reserved = DB::table('supplier_invoice_items as sii')
                ->join('supplier_invoices as si', 'si.id', '=', 'sii.supplier_invoice_id')
                ->where('sii.purchase_order_item_id', $line->id)
                ->where('si.status', '<>', SupplierInvoice::STATUS_CANCELLED)
                ->where('si.id', $excludeInvoiceId)
                ->sum('sii.quantity');
            return array_sum(array_column($buckets, 'available_milli')) + $this->toMilli($reserved ?: 0);
        }
        return array_sum(array_column($buckets, 'available_milli'));
    }

    private function serviceRemainingMilli(PurchaseOrderItem $line, ?int $excludeInvoiceId): int
    {
        $query = DB::table('supplier_invoice_items as sii')
            ->join('supplier_invoices as si', 'si.id', '=', 'sii.supplier_invoice_id')
            ->where('sii.purchase_order_item_id', $line->id)
            ->where('si.status', '<>', SupplierInvoice::STATUS_CANCELLED);
        if ($excludeInvoiceId) { $query->where('si.id', '<>', $excludeInvoiceId); }
        $billed = $this->toMilli($query->sum('sii.quantity') ?: 0);
        return max(0, $this->toMilli($line->quantity) - $billed);
    }

    private function assertOrderCanBeBilled(PurchaseOrder $order): void
    {
        if (!in_array($order->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED, PurchaseOrder::STATUS_RECEIVED], true)) {
            throw ValidationException::withMessages(['purchase_order' => [__('supplier_invoices.errors.order_not_billable')]]);
        }
    }

    private function toMilli($value): int
    {
        $text = (string) $value;
        if (!preg_match('/^\d+(?:\.\d{1,3})?$/D', $text)) { return 0; }
        $parts = explode('.', $text, 2);
        $whole = ltrim($parts[0], '0') ?: '0';
        return ((int) $whole * 1000) + (int) str_pad($parts[1] ?? '', 3, '0');
    }

    private function fromMilli(int $milli): string
    {
        return intdiv($milli, 1000) . '.' . str_pad((string) ($milli % 1000), 3, '0', STR_PAD_LEFT);
    }
}
