<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BusinessPaymentVerification extends Model
{
    protected $table = 'business_payment_verifications';

    protected $fillable = [
        'business_id', 'reference', 'payment_method', 'amount',
        'paid_on', 'proof_path', 'status', 'admin_note',
    ];

    protected $dates = ['paid_on'];

    public function business()
    {
        return $this->belongsTo(BusinessSetting::class, 'business_id');
    }
}
