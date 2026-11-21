<?php

namespace App\Http\Middleware\Roles\UserManage;

use Closure;

use App\Http\Controllers\RoleManageController;
use Illuminate\Support\Facades\Session;

class Edit
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

        $UserEdit=config('role_manage.User.Edit');
        if ($UserEdit){ // Edit
            return $next($request);
        }else{
            Session::flash('error', __('ui.messages.permission_denied'));
            return redirect()->back();
        }
    }
}
