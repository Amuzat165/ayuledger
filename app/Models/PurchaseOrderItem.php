<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderItem extends \App\TenantScopedModel
{
    use SoftDeletes;

    public const CLASSIFICATION_STOCK = 'stock';
    public const CLASSIFICATION_SERVICE = 'service';
    public const CLASSIFICATION_UNCLASSIFIED = 'unclassified';

    protected $fillable = [
        'purchase_order_id', 'inventory_item_id', 'inventory_item_name_snapshot',
        'description', 'unit', 'inventory_classification', 'quantity', 'reference_unit_price',
        'agreed_unit_price', 'line_total', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'reference_unit_price' => 'decimal:2',
        'agreed_unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(Inventory::class, 'inventory_item_id')->withTrashed();
    }
}
