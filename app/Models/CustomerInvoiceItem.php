<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerInvoiceItem extends \App\TenantScopedModel
{
    use SoftDeletes;
    protected $guarded = [];

    public function invoice()
    {
        return $this->belongsTo(CustomerInvoice::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'item_id')->with('type');
    }

}
