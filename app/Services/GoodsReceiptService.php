<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceivedItem;
use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoodsReceiptService
{
    public function saveDraft(PurchaseOrder $purchaseOrder, User $user, array $data): GoodsReceipt
    {
        return DB::transaction(function () use ($purchaseOrder, $user, $data) {
            $existing = GoodsReceipt::where('submission_token', $data['submission_token'])->lockForUpdate()->first();
            if ($existing) {
                if ((int) $existing->purchase_order_id !== (int) $purchaseOrder->id) {
                    throw ValidationException::withMessages(['submission_token' => [__('goods_received.errors.invalid_submission')]]);
                }
                return $existing;
            }

            $order = PurchaseOrder::whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            $this->assertReceivable($order);
            $orderItems = $order->items()->lockForUpdate()->get()->keyBy('id');
            $selected = $this->validatedLines($order, $orderItems, $data['received_quantities'] ?? [], null);
            $receipt = GoodsReceipt::create([
                'submission_token' => $data['submission_token'],
                'purchase_order_id' => $order->id,
                'vendor_id' => $order->vendor_id,
                'branch_id' => $order->branch_id,
                'received_date' => $data['received_date'],
                'status' => GoodsReceipt::STATUS_DRAFT,
                'created_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($selected as $line) {
                GoodsReceivedItem::create([
                    'goods_receipt_id' => $receipt->id,
                    'purchase_order_item_id' => $line->id,
                    'inventory_item_id' => $line->inventory_item_id,
                    'item_description_snapshot' => $line->inventory_item_name_snapshot ?: $line->description,
                    'unit_snapshot' => $line->unit,
                    'received_quantity' => $line->requested_quantity,
                ]);
            }
            return $receipt;
        });
    }

    public function confirm(int $receiptId, User $user): GoodsReceipt
    {
        $initial = GoodsReceipt::findOrFail($receiptId);

        return DB::transaction(function () use ($initial, $user) {
            $order = PurchaseOrder::whereKey($initial->purchase_order_id)->lockForUpdate()->firstOrFail();
            $receipt = GoodsReceipt::whereKey($initial->id)->lockForUpdate()->firstOrFail();
            if ($receipt->status === GoodsReceipt::STATUS_CONFIRMED) {
                return $receipt;
            }
            if ($receipt->status !== GoodsReceipt::STATUS_DRAFT) {
                throw ValidationException::withMessages(['receipt' => [__('goods_received.errors.order_not_receivable')]]);
            }
            $this->assertReceivable($order);
            if ((int) $receipt->vendor_id !== (int) $order->vendor_id || (int) $receipt->branch_id !== (int) $order->branch_id) {
                throw ValidationException::withMessages(['purchase_order' => [__('goods_received.errors.receipt_order_mismatch')]]);
            }

            $orderItems = $order->items()->lockForUpdate()->get()->keyBy('id');
            $receiptItems = $receipt->items()->lockForUpdate()->get();
            if ($receiptItems->isEmpty()) {
                throw ValidationException::withMessages(['received_quantities' => [__('goods_received.errors.at_least_one_quantity')]]);
            }

            foreach ($receiptItems as $receiptItem) {
                $line = $orderItems->get($receiptItem->purchase_order_item_id);
                if (!$line || (int) $receiptItem->inventory_item_id !== (int) $line->inventory_item_id) {
                    throw ValidationException::withMessages(['received_quantities' => [__('goods_received.errors.line_not_in_order')]]);
                }
                $quantityMilli = $this->toMilli($receiptItem->received_quantity);
                if ($quantityMilli <= 0) {
                    throw ValidationException::withMessages(['received_quantities' => [__('goods_received.errors.quantity_must_be_positive')]]);
                }
                $previousMilli = $this->confirmedMilli($line->id, $receipt->id);
                $orderedMilli = $this->toMilli($line->quantity);
                if ($quantityMilli > $orderedMilli - $previousMilli) {
                    throw ValidationException::withMessages(['received_quantities' => [__('goods_received.errors.over_receiving')]]);
                }

                if ($line->inventory_item_id) {
                    $inventory = Inventory::withTrashed()->whereKey($line->inventory_item_id)->lockForUpdate()->first();
                    if (!$inventory) {
                        throw ValidationException::withMessages(['received_quantities' => [__('goods_received.errors.inventory_unavailable')]]);
                    }
                    if ($inventory->track_stock === null) {
                        throw ValidationException::withMessages(['received_quantities' => [__('goods_received.errors.classify_inventory_first')]]);
                    }
                    if ($inventory->tracksStock()) {
                        if ($quantityMilli % 1000 !== 0) {
                            throw ValidationException::withMessages(['received_quantities' => [__('goods_received.errors.whole_stock_quantity')]]);
                        }
                        if ((int) $inventory->branch_id !== (int) $order->branch_id) {
                            throw ValidationException::withMessages(['received_quantities' => [__('goods_received.errors.inventory_branch_mismatch')]]);
                        }
                        $inventory->increment('quantity_at_hand', (int) ($quantityMilli / 1000));
                    }
                }
            }

            $receipt->receipt_number = $this->nextReceiptNumber();
            $receipt->status = GoodsReceipt::STATUS_CONFIRMED;
            $receipt->received_by = $user->id;
            $receipt->save();

            $complete = true;
            foreach ($orderItems as $line) {
                if ($this->confirmedMilli($line->id, null) < $this->toMilli($line->quantity)) {
                    $complete = false;
                    break;
                }
            }
            $order->status = $complete ? PurchaseOrder::STATUS_RECEIVED : PurchaseOrder::STATUS_PARTIALLY_RECEIVED;
            $order->updated_by = $user->id;
            $order->save();
            return $receipt;
        });
    }

    private function validatedLines(PurchaseOrder $order, $orderItems, array $quantities, ?int $excludeReceiptId): array
    {
        $selected = [];
        foreach ($quantities as $itemId => $quantity) {
            if ($quantity === null || $quantity === '') {
                continue;
            }
            if (!ctype_digit((string) $itemId) || !$orderItems->has((int) $itemId)) {
                throw ValidationException::withMessages(['received_quantities' => [__('goods_received.errors.line_not_in_order')]]);
            }
            $line = $orderItems->get((int) $itemId);
            $requestedMilli = $this->toMilli($quantity);
            if ($requestedMilli <= 0) {
                throw ValidationException::withMessages(['received_quantities.' . $itemId => [__('goods_received.errors.quantity_must_be_positive')]]);
            }
            $remainingMilli = $this->toMilli($line->quantity) - $this->confirmedMilli($line->id, $excludeReceiptId);
            if ($requestedMilli > $remainingMilli) {
                throw ValidationException::withMessages(['received_quantities.' . $itemId => [__('goods_received.errors.over_receiving')]]);
            }
            $line->requested_quantity = number_format($requestedMilli / 1000, 3, '.', '');
            $selected[] = $line;
        }
        if (!$selected) {
            throw ValidationException::withMessages(['received_quantities' => [__('goods_received.errors.at_least_one_quantity')]]);
        }
        return $selected;
    }

    private function confirmedMilli(int $purchaseOrderItemId, ?int $excludeReceiptId): int
    {
        $query = DB::table('goods_received_items')
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_received_items.goods_receipt_id')
            ->where('goods_received_items.purchase_order_item_id', $purchaseOrderItemId)
            ->where('goods_receipts.status', GoodsReceipt::STATUS_CONFIRMED);
        if ($excludeReceiptId) {
            $query->where('goods_receipts.id', '<>', $excludeReceiptId);
        }
        $rows = $query->pluck('goods_received_items.received_quantity');
        return $rows->sum(function ($quantity) { return $this->toMilli($quantity); });
    }

    private function assertReceivable(PurchaseOrder $order): void
    {
        if (!in_array($order->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED], true)) {
            throw ValidationException::withMessages(['purchase_order' => [__('goods_received.errors.order_not_receivable')]]);
        }
    }

    private function toMilli($quantity): int
    {
        $parts = explode('.', (string) $quantity, 2);
        $whole = (int) ($parts[0] ?? 0);
        $fraction = str_pad(substr($parts[1] ?? '', 0, 3), 3, '0');
        return ($whole * 1000) + (int) $fraction;
    }

    private function nextReceiptNumber(): string
    {
        $now = now();
        DB::table('invoice_sequences')->insertOrIgnore([
            'document_type' => 'goods_receipt', 'current_number' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $sequence = DB::table('invoice_sequences')->where('document_type', 'goods_receipt')->lockForUpdate()->first();
        if (!$sequence) {
            throw new \RuntimeException('Goods Received numbering is unavailable.');
        }
        $next = (int) $sequence->current_number + 1;
        DB::table('invoice_sequences')->where('document_type', 'goods_receipt')->update(['current_number' => $next, 'updated_at' => $now]);
        return 'GR-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
