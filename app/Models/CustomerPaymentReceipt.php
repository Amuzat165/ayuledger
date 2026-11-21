<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPaymentReceipt extends \App\TenantScopedModel
{
    protected $table = 'customer_payment_receipts';
    protected $guarded = [];
    protected $dates = ['received_at'];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function allocations() { return $this->hasMany(CustomerPaymentAllocation::class, 'receipt_id'); }
    public function deposit() { return $this->hasOne(CustomerDeposit::class, 'source_payment_receipt_id'); }
    public function paymentMode() { return $this->belongsTo(\App\BankCash::class, 'payment_mode_id'); }
}
