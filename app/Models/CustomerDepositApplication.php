<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class CustomerDepositApplication extends \App\TenantScopedModel
{
    protected $table = 'customer_deposit_applications';
    protected $guarded = [];
    protected $dates = ['applied_at'];

    public function deposit() { return $this->belongsTo(CustomerDeposit::class, 'customer_deposit_id'); }
    public function invoice() { return $this->belongsTo(CustomerInvoice::class, 'invoice_id'); }
    public function operator() { return $this->belongsTo(User::class, 'applied_by'); }
}
