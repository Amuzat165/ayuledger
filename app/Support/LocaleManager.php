<?php

namespace App\Support;

class LocaleManager
{
    const DEFAULT_LOCALE = 'main';

    public static function supportedLocales()
    {
        return [
            'main' => 'English',
            'fr' => "Fran\u{00E7}ais",
            'ar' => "\u{0627}\u{0644}\u{0639}\u{0631}\u{0628}\u{064A}\u{0629}",
            'pt' => "Portugu\u{00EA}s",
            'es' => "Espa\u{00F1}ol",
            'zh' => "\u{7B80}\u{4F53}\u{4E2D}\u{6587}",
        ];
    }

    public static function normalize($locale)
    {
        if (!is_string($locale)) {
            return null;
        }

        $locale = str_replace('-', '_', trim($locale));
        $aliases = [
            'en' => 'main',
            'en_US' => 'main',
            'eng' => 'main',
            'zh_CN' => 'zh',
            'zh_SG' => 'zh',
        ];
        $locale = isset($aliases[$locale]) ? $aliases[$locale] : $locale;

        return array_key_exists($locale, self::supportedLocales()) ? $locale : null;
    }

    public static function isLegacyLocaleAvailable($locale)
    {
        return is_string($locale)
            && preg_match('/^[a-z]{2,3}(?:_[A-Z]{2})?$/D', $locale) === 1
            && is_dir(resource_path('lang/' . $locale));
    }

    public static function direction($locale)
    {
        return self::normalize($locale) === 'ar' ? 'rtl' : 'ltr';
    }
}