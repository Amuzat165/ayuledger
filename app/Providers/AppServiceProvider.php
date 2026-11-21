<?php

namespace App\Providers;

use App\Setting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();
        
        Schema::defaultStringLength(191);
        
        if (!app()->runningInConsole()) {
            // Share settings
            $businessId = Auth::check() ? (int) Auth::user()->business_id : 0;
            $systemCacheKey = 'system_settings_' . $businessId;
            $generalCacheKey = 'general_settings_' . $businessId;
            if (Cache::get($systemCacheKey) && Cache::get($systemCacheKey) != null) {
                $system_settings = json_decode(Cache::get($systemCacheKey), true);
            } else {
                $system_settings_info = $businessId
                    ? Setting::where('settings_name', 'System Settings')->where('business_id', $businessId)->first()
                    : Setting::where('settings_name', 'System Settings')->first();
                $system_settings = json_decode($system_settings_info->content, true);
            }
            if (Cache::get($generalCacheKey) && Cache::get($generalCacheKey) != null) {
                $general_settings = json_decode(Cache::get($generalCacheKey), true);
            } else {
                $system_settings_info = $businessId
                    ? Setting::where('settings_name', 'General Settings')->where('business_id', $businessId)->first()
                    : Setting::where('settings_name', 'General Settings')->first();
                $general_settings = json_decode($system_settings_info->content, true);
            }
            View::share('general_settings', $general_settings);
            View::share('system_settings', $system_settings);
            if ($system_settings['is_rtl'] == 'Yes') {
                $is_rtl = 1;
            } else {
                $is_rtl = 0;
            }
            View::share('is_rtl', $is_rtl);

        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
