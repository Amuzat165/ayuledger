<?php

namespace Tests\Unit;

use App\Language;
use App\Http\Controllers\LocaleController;
use App\Http\Middleware\SetLocale;
use App\Support\LanguagePreference;
use App\Support\LocaleManager;
use Database\Seeders\SupportedLanguagesSeeder;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Tests\TestCase;

class DatabaseLanguagePreferenceTest extends TestCase
{
    private function configuredLanguages()
    {
        $records = [];
        foreach ([
            ['english', 'eng'], ['french', 'fr'], ['arabic', 'ar'],
            ['portuguese', 'pt'], ['spanish', 'es'], ['chinese', 'zh'],
        ] as $index => [$name, $code]) {
            $language = new Language(['name' => $name, 'code' => $code]);
            $language->id = $index + 1;
            $records[] = $language;
        }

        return collect($records);
    }

    public function test_all_six_approved_locales_map_to_existing_language_records()
    {
        $preference = new LanguagePreference();
        $languages = $this->configuredLanguages();

        $this->assertSame(['main', 'fr', 'ar', 'pt', 'es', 'zh'], array_keys(LocaleManager::supportedLocales()));
        foreach (array_keys(LocaleManager::supportedLocales()) as $locale) {
            $this->assertNotNull($preference->findLocaleRecord($languages, $locale), $locale);
        }
        $this->assertSame(['ar', 'pt'], array_column(SupportedLanguagesSeeder::languageDefinitions(), 'code'));
    }

    public function test_all_six_supported_choices_persist_the_existing_language_id()
    {
        $preference = new LanguagePreference();
        $languages = $this->configuredLanguages();

        foreach (['main' => 1, 'fr' => 2, 'ar' => 3, 'pt' => 4, 'es' => 5, 'zh' => 6] as $locale => $expectedId) {
            $user = new class {
                public $language_id;
                public $saved = false;
                public function save() { $this->saved = true; }
            };

            $this->assertTrue($preference->persist($user, $locale, $languages));
            $this->assertSame($expectedId, $user->language_id);
            $this->assertTrue($user->saved);
        }
    }

    public function test_authenticated_header_selection_persists_the_user_preference()
    {
        $user = new class {
            public $language_id;
            public $saved = false;
            public function save() { $this->saved = true; }
        };
        $preference = new class extends LanguagePreference {
            public function activeLanguages()
            {
                $language = new Language(['name' => 'arabic', 'code' => 'ar']);
                $language->id = 3;
                return collect([$language]);
            }
        };
        $this->app->instance(LanguagePreference::class, $preference);
        $session = new Store('header-language-selection', new ArraySessionHandler(120));
        $request = Request::create('/locale', 'POST', ['locale' => 'ar']);
        $request->setLaravelSession($session);
        $request->setUserResolver(function () use ($user) { return $user; });

        (new LocaleController())->update($request);

        $this->assertSame(3, $user->language_id);
        $this->assertTrue($user->saved);
        $this->assertSame('ar', $session->get('app_locale'));
        $this->assertSame('ar', app()->getLocale());
    }

    public function test_saved_language_is_respected_without_session_and_direction_is_correct()
    {
        foreach ([['ar', 'rtl'], ['main', 'ltr']] as [$locale, $direction]) {
            $session = new Store('fresh-locale-session', new ArraySessionHandler(120));
            $request = Request::create('/dashboard');
            $request->setLaravelSession($session);
            $request->setUserResolver(function () use ($locale) {
                return (object) ['language' => (object) ['code' => $locale === 'main' ? 'eng' : $locale]];
            });

            (new SetLocale())->handle($request, function () {
                return response('ok');
            });

            $this->assertSame($locale, app()->getLocale());
            $this->assertSame($direction === 'rtl' ? 1 : 0, view()->shared('is_rtl'));
        }
    }

    public function test_authenticated_saved_language_wins_over_stale_session_but_guest_keeps_session_choice()
    {
        $session = new Store('locale-precedence', new ArraySessionHandler(120));
        $session->put('app_locale', 'main');
        $request = Request::create('/dashboard');
        $request->setLaravelSession($session);
        $request->setUserResolver(function () {
            return (object) ['language' => (object) ['code' => 'ar']];
        });

        (new SetLocale())->handle($request, function () { return response('ok'); });
        $this->assertSame('ar', app()->getLocale());

        $guestRequest = Request::create('/login');
        $guestRequest->setLaravelSession($session);
        (new SetLocale())->handle($guestRequest, function () { return response('ok'); });
        $this->assertSame('main', app()->getLocale());
    }
}
