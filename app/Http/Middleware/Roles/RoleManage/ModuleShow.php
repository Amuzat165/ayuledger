<?php

namespace App\Http\Middleware\Roles\RoleManage;

use Closure;

use App\Http\Controllers\RoleManageController;
use Illuminate\Support\Facades\Session;

class ModuleShow
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if (config('role_manage.RoleManager.All')){
            return $next($request);
        }else{
            Session::flash('error', __('ui.messages.permission_denied'));
            return redirect()->route('dashboard');
        }

    }
}
