<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait SupportsTenantIsolation
{
    private static $resolvingAuthenticatedUser = false;

    protected static function bootSupportsTenantIsolation()
    {
        static::addGlobalScope('business', function (Builder $builder) {
            $user = null;
            if (!self::$resolvingAuthenticatedUser) {
                self::$resolvingAuthenticatedUser = true;
                try {
                    $user = Auth::user();
                } finally {
                    self::$resolvingAuthenticatedUser = false;
                }
            }
            if ($user && $user->business_id) {
                $builder->where($builder->getModel()->getTable() . '.business_id', $user->business_id);
            }
        });

        static::creating(function ($model) {
            $user = null;
            if (!self::$resolvingAuthenticatedUser) {
                self::$resolvingAuthenticatedUser = true;
                try {
                    $user = Auth::user();
                } finally {
                    self::$resolvingAuthenticatedUser = false;
                }
            }
            if (!$model->business_id && $user && $user->business_id) {
                $model->business_id = $user->business_id;
            }
        });
    }
}
