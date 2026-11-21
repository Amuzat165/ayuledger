<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierInvoiceItem extends \App\TenantScopedModel
{
    protected $fillable = [
        'supplier_invoice_id', 'purchase_order_item_id', 'inventory_id', 'description_snapshot',
        'unit_snapshot', 'track_stock_snapshot', 'quantity', 'purchase_order_unit_price',
        'supplier_unit_price', 'line_total',
    ];

    protected $casts = [
        'track_stock_snapshot' => 'boolean', 'quantity' => 'decimal:3',
        'purchase_order_unit_price' => 'decimal:2', 'supplier_unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice() { return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id'); }
    public function purchaseOrderItem() { return $this->belongsTo(PurchaseOrderItem::class)->withTrashed(); }
    public function inventory() { return $this->belongsTo(Inventory::class, 'inventory_id')->withTrashed(); }
    public function receiptAllocations() { return $this->hasMany(SupplierInvoiceReceiptAllocation::class); }
}
