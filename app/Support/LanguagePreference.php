<?php

namespace App\Support;

use App\Language;

class LanguagePreference
{
    public function activeLanguages()
    {
        return Language::whereNull('deleted_at')->get();
    }

    public function findLocaleRecord($languages, $locale)
    {
        $locale = LocaleManager::normalize($locale);

        if (!$locale) {
            return null;
        }

        foreach ($languages as $language) {
            if (LocaleManager::normalize($language->code) === $locale) {
                return $language;
            }
        }

        return null;
    }

    public function localeForLanguageId($languages, $languageId)
    {
        foreach ($languages as $language) {
            if ((string) $language->id === (string) $languageId) {
                return LocaleManager::normalize($language->code);
            }
        }

        return null;
    }

    public function persist($user, $locale, $languages)
    {
        $language = $this->findLocaleRecord($languages, $locale);

        if (!$user || !$language) {
            return false;
        }

        $user->language_id = $language->id;
        $user->save();

        return true;
    }

    public function persistActive($user, $locale)
    {
        return $this->persist($user, $locale, $this->activeLanguages());
    }
}
