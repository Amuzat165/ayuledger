<?php

namespace App\Http\Middleware\Roles\Inventory;

use Closure;

class Create
{
    public function handle($request, Closure $next)
    {
        abort_unless((bool) config('role_manage.Inventory.Create', 0), 403);

        return $next($request);
    }
}
