<?php

// CustomerInvoice.php
namespace App\Models;

use App\BankCash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\GeneratesInvoiceNumber;

class CustomerInvoice extends \App\TenantScopedModel
{
    use SoftDeletes;
    use GeneratesInvoiceNumber;

    protected const INVOICE_SEQUENCE = 'customer';

    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(CustomerInvoiceItem::class, 'invoice_id');
    }

}
