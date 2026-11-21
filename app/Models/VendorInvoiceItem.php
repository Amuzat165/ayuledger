<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorInvoiceItem extends \App\TenantScopedModel
{
    use SoftDeletes;
    use HasFactory;
    protected $guarded = [];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function invoice()
    {
        return $this->belongsTo(VendorInvoice::class, 'invoice_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'item_id')->with('type');
    }
}
