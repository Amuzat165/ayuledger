<?php

namespace App\Http\Middleware;

use App\Support\LocaleManager;
use Closure;
use Illuminate\Support\Facades\View;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        if ($user) {
            // An explicit header-dropdown choice that could not be saved to the
            // user's profile (e.g. no matching row in `languages`, as with Arabic)
            // must win over the stored preference for this session. LocaleController
            // clears it once a choice is persisted; ProfileController clears it on save.
            $locale = LocaleManager::normalize($request->session()->get('app_locale_override'));

            $preferredLocale = $user->language ? $user->language->code : null;

            if (!$locale) {
                $locale = LocaleManager::normalize($preferredLocale);
            }

            if (!$locale && LocaleManager::isLegacyLocaleAvailable($preferredLocale)) {
                $locale = $preferredLocale;
            }

            if (!$locale) {
                $defaultLocale = app()->getLocale();
                $locale = LocaleManager::normalize($defaultLocale);

                if (!$locale && LocaleManager::isLegacyLocaleAvailable($defaultLocale)) {
                    $locale = $defaultLocale;
                }
            }

            $locale = $locale ?: LocaleManager::DEFAULT_LOCALE;
        } else {
            $locale = LocaleManager::normalize($request->session()->get('app_locale'))
                ?: LocaleManager::DEFAULT_LOCALE;
        }

        app()->setLocale($locale);

        View::share('is_rtl', LocaleManager::direction($locale) === 'rtl' ? 1 : 0);

        return $next($request);
    }
}
