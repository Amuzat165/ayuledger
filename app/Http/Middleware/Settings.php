<?php

namespace App\Http\Middleware;

use Closure;

use App\Setting;
use App\Language;
use Illuminate\Support\Facades\Cache;
use App\Support\VatConfiguration;
use App\Support\InvoiceDiscountConfiguration;
use App\Support\WhtConfiguration;

class Settings
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
        $businessId = (int) optional($request->user())->business_id;
        $generalCacheKey = 'general_settings_' . $businessId;
        $systemCacheKey = 'system_settings_' . $businessId;

        if (Cache::get($generalCacheKey) !== null) {
            $general = json_decode(Cache::get($generalCacheKey), true);
        } else {
            $generalSettingQuery = Setting::where('settings_name', 'General Settings');
            if ($businessId) {
                $generalSettingQuery->where('business_id', $businessId);
            }
            $general_setting = $generalSettingQuery->first();
            if (!$general_setting) {
                throw new \RuntimeException('General Settings are missing.');
            }
            $general = json_decode($general_setting->content, true);
            Cache::put($generalCacheKey, $general_setting->content);
        }

        if (!is_array($general)) {
            throw new \RuntimeException('General Settings are invalid.');
        }
        $vatPercentage = app(VatConfiguration::class)->validatePercentage($general['vat_percentage'] ?? null);
        $discountEnabled = app(InvoiceDiscountConfiguration::class)
            ->enabledFromValue($general['discount_enabled'] ?? false);
        $whtConfiguration = app(WhtConfiguration::class)->withDefaults($general);
        $activeManualWhtRate = app(WhtConfiguration::class)->activeManualRate($whtConfiguration);

        if (Cache::get($systemCacheKey) !== null) {
            $system = json_decode(Cache::get($systemCacheKey), true);
        } else {
            $systemSettingQuery = Setting::where('settings_name', 'System Settings');
            if ($businessId) {
                $systemSettingQuery->where('business_id', $businessId);
            }
            $system_setting = $systemSettingQuery->first();
            $system = json_decode($system_setting->content, true);
            Cache::put($systemCacheKey, $system_setting->content);
        }

        //        Time Zone
        date_default_timezone_set($system['timezone']);
        config([
            'settings.company_name' => $general['company_name'],
            'settings.contract_person' => $general['contract_person'],
            'settings.email' => $general['email'],
            'settings.phone' => $general['phone'],
            'settings.address_1' => $general['address_1'],
            'settings.address_2' => $general['address_2'],
            'settings.city' => $general['city'],
            'settings.state' => $general['state'],
            'settings.zip_code' => $general['zip_code'],
            'settings.company_logo' => $general['company_logo'],
            'settings.header_image' => $general['header_image'] ?? null,
            'settings.vat_percentage' => $vatPercentage,
            'settings.discount_enabled' => $discountEnabled,
            'settings.discount_percentage' => $general['discount_percentage'] ?? 0,
            'settings.wht_enabled' => $whtConfiguration['wht_enabled'],
            'settings.wht_mode' => $whtConfiguration['wht_mode'],
            'settings.wht_manual_rate' => $activeManualWhtRate ?? 0.0,
            'settings.company_color' => $general['company_color'] ?? '#00897B',

            'settings.date_format' => $system['date_format'],
            'settings.currency_code' => $system['currency_code'],
            'settings.currency_symbol' => $system['currency_symbol'],
            'settings.is_code' => $system['is_code'],
            'settings.currency_position' => $system['currency_position'],
            'settings.fixed_asset_schedule_starting_date' => $system['fixed_asset_schedule_starting_date'],
        ]);

        // Load default language and set app locale
        try {
            if (Cache::get('default_language') && Cache::get('default_language') != null) {
                $language_code = Cache::get('default_language');
            } else {
                $default_language = Language::where('is_default', 1)->first();
                if ($default_language) {
                    $language_code = $default_language->code;
                    Cache::put('default_language', $language_code);
                } else {
                    $language_code = config('app.locale', 'eng');
                }
            }
            
            // Set the app locale
            app()->setLocale($language_code);
            \Illuminate\Support\Facades\App::setLocale($language_code);
        } catch (\Exception $e) {
            // If language loading fails, use default locale
            app()->setLocale(config('app.locale', 'eng'));
        }

        return $next($request);
    }
}
