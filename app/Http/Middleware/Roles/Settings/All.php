<?php

namespace App\Http\Middleware\Roles\Settings;

use Closure;
use Illuminate\Support\Facades\Session;

class All
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
        if (config('role_manage.Settings.All')){
            return $next($request);
        }else{
            Session::flash('error', __('ui.messages.permission_denied'));
            return redirect()->route('dashboard');
        }
    }
}
