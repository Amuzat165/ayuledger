<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\SupportsTenantIsolation;

class SiteSetting extends Model
{
    use SupportsTenantIsolation;
    protected $fillable = ['key_name', 'value', 'business_id'];
    protected $guarded = [];
}
