<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends TenantScopedModel
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'gender',
        'designation',
        'phone_number',
        'NID',
        'permanent_address',
        'permanent_address',
        'avatar',
        'education',
        'description'
    ];
    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
