<?php

namespace App\Http\Middleware\Roles\UserManage;

use Closure;

use Illuminate\Support\Facades\Session;

class TrashShow
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

        if (config('role_manage.User.TrashShow')){ // Trash Show Module
            return $next($request);
        }else{
            Session::flash('error', __('ui.messages.permission_denied'));
            return redirect()->back();
        }

    }
}
