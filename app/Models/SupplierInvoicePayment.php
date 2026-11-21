<?php

namespace App\Models;

use App\BankCash;
use App\User;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoicePayment extends \App\TenantScopedModel
{
    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'date',
        'payment_amount' => 'decimal:2',
        'previous_amount_remaining' => 'decimal:2',
        'new_amount_remaining' => 'decimal:2',
    ];

    public function supplierInvoice() { return $this->belongsTo(SupplierInvoice::class); }
    public function paymentMode() { return $this->belongsTo(BankCash::class, 'payment_mode_id')->withTrashed(); }
    public function creator() { return $this->belongsTo(User::class, 'created_by')->withTrashed(); }
}
