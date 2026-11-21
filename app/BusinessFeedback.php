<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BusinessFeedback extends TenantScopedModel
{
    protected $table = 'business_feedback';

    protected $fillable = ['business_id', 'user_id', 'subject', 'message', 'status'];

    public function business()
    {
        return $this->belongsTo(BusinessSetting::class, 'business_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
