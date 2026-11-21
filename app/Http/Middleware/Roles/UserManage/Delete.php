<?php

namespace App\Http\Middleware\Roles\UserManage;

use Closure;
use Illuminate\Support\Facades\Session;
class Delete
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

        $UserDelete=config('role_manage.User.Delete');
        if ($UserDelete){ // Delete
            return $next($request);
        }else{
            Session::flash('error', __('ui.messages.permission_denied'));
            return redirect()->back();
        }


    }
}
