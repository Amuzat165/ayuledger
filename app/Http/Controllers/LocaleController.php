<?php

namespace App\Http\Controllers;

use App\Support\LanguagePreference;
use App\Support\LocaleManager;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request)
    {
        $locale = LocaleManager::normalize($request->input('locale'))
            ?: LocaleManager::DEFAULT_LOCALE;

        // Preserve the explicit choice for guests and as a fallback when an
        // authenticated user's preference cannot be saved.
        $request->session()->put('app_locale', $locale);
        app()->setLocale($locale);

        $user = $request->user();
        if ($user) {
            $persisted = false;
            try {
                $persisted = app(LanguagePreference::class)->persistActive($user, $locale);
            } catch (\Throwable $exception) {
                // Keep this selection available to SetLocale for this session.
            }

            if ($persisted) {
                $request->session()->forget('app_locale_override');
            } else {
                $request->session()->put('app_locale_override', $locale);
            }
        }

        return redirect()->back();
    }
}
