<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

abstract class TenantScopedModel extends Model
{
    use SupportsTenantIsolation;
}
