<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPaymentAllocation extends \App\TenantScopedModel
{
    protected $table = 'customer_payment_allocations';
    protected $guarded = [];
    protected $dates = ['allocated_at'];

    public function receipt() { return $this->belongsTo(CustomerPaymentReceipt::class, 'receipt_id'); }
    public function invoice() { return $this->belongsTo(CustomerInvoice::class, 'invoice_id'); }
}
