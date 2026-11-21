<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TenantMembership extends TenantScopedModel
{
    protected $fillable = [
        'tenant_id',
        'business_id',
        'user_id',
        'role_manage_id',
        'status',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function roleManage()
    {
        return $this->belongsTo(RoleManage::class, 'role_manage_id');
    }
}
