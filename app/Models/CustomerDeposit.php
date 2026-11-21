<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDeposit extends \App\TenantScopedModel
{
    protected $table = 'customer_deposits';
    protected $guarded = [];
    protected $dates = ['created_at', 'updated_at'];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function sourceReceipt() { return $this->belongsTo(CustomerPaymentReceipt::class, 'source_payment_receipt_id'); }
    public function sourceInvoice() { return $this->belongsTo(CustomerInvoice::class, 'source_invoice_id'); }
    public function applications() { return $this->hasMany(CustomerDepositApplication::class, 'customer_deposit_id'); }
}
