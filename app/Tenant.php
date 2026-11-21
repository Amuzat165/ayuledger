<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    public function memberships()
    {
        return $this->hasMany(TenantMembership::class);
    }

    public function users()
    {
        return $this->hasManyThrough(
            User::class,
            TenantMembership::class,
            'tenant_id',
            'id',
            'id',
            'user_id'
        );
    }

    public function businessSetting()
    {
        return $this->hasOne(BusinessSetting::class);
    }
}
