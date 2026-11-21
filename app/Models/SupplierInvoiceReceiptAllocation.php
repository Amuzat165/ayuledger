<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierInvoiceReceiptAllocation extends \App\TenantScopedModel
{
    protected $fillable = ['supplier_invoice_item_id', 'goods_received_item_id', 'allocated_quantity'];
    protected $casts = ['allocated_quantity' => 'decimal:3'];

    public function supplierInvoiceItem() { return $this->belongsTo(SupplierInvoiceItem::class); }
    public function goodsReceivedItem() { return $this->belongsTo(GoodsReceivedItem::class); }
}
