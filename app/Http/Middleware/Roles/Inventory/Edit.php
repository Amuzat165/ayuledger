<?php

namespace App\Http\Middleware\Roles\Inventory;

use Closure;

class Edit
{
    public function handle($request, Closure $next)
    {
        abort_unless((bool) config('role_manage.Inventory.Edit', 0), 403);

        return $next($request);
    }
}
