<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceivedItem extends \App\TenantScopedModel
{
    protected $fillable = [
        'goods_receipt_id', 'purchase_order_item_id', 'inventory_item_id',
        'item_description_snapshot', 'unit_snapshot', 'received_quantity',
    ];

    protected $casts = ['received_quantity' => 'decimal:3'];

    public function goodsReceipt() { return $this->belongsTo(GoodsReceipt::class); }
    public function purchaseOrderItem() { return $this->belongsTo(PurchaseOrderItem::class)->withTrashed(); }
    public function inventoryItem() { return $this->belongsTo(Inventory::class, 'inventory_item_id')->withTrashed(); }
}
