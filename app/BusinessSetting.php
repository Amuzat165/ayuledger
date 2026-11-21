<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = [
        'id', 'tenant_id', 'business_name', 'business_email', 'phone', 'address',
        'currency', 'timezone', 'subscription_plan', 'subscription_interval', 'subscription_status',
        'max_users_override', 'max_branches_override',
        'registration_status', 'subscription_starts_at', 'subscription_expires_at',
        'payment_reference', 'payment_method', 'payment_amount',
        'payment_submitted_at', 'approved_at', 'approved_by', 'admin_note',
    ];

    protected $dates = [
        'subscription_starts_at', 'subscription_expires_at',
        'payment_submitted_at', 'approved_at',
    ];

    protected $casts = [
        'max_users_override' => 'integer',
        'max_branches_override' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function feedback()
    {
        return $this->hasMany(BusinessFeedback::class, 'business_id');
    }

    public function paymentVerifications()
    {
        return $this->hasMany(BusinessPaymentVerification::class, 'business_id');
    }
}
