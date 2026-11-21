<?php

namespace Tests\Feature;

use App\Support\LocaleManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class MultilingualLocalizationTest extends TestCase
{
    private const BATCH_LOCALES = ['fr', 'es', 'pt', 'zh'];

    private const FILE_COUNTS = [
        'root' => 175,
        'ui' => 118,
        'financial' => 445,
        'header' => 9,
        'aside-lang' => 6,
        'auth' => 6,
        'passwords' => 5,
        'pagination' => 2,
        'validation' => 83,
    ];

    public function test_supported_locale_mapping_and_arabic_direction()
    {
        $this->assertSame(['main', 'fr', 'ar', 'pt', 'es', 'zh'], array_keys(LocaleManager::supportedLocales()));
        $this->assertSame('main', LocaleManager::normalize('en'));
        $this->assertSame('fr', LocaleManager::normalize('fr'));
        $this->assertSame('es', LocaleManager::normalize('es'));
        $this->assertSame('pt', LocaleManager::normalize('pt'));
        $this->assertSame('zh', LocaleManager::normalize('zh-CN'));
        $this->assertSame('ar', LocaleManager::normalize('ar'));
        $this->assertSame('rtl', LocaleManager::direction('ar'));

        foreach (['main', 'fr', 'es', 'pt', 'zh'] as $locale) {
            $this->assertSame('ltr', LocaleManager::direction($locale));
        }

        $this->assertSame('Financial Workspace', __('root.menu.financial_workspace'));
        $this->assertSame('Administration', __('root.menu.administration'));
        $this->assertSame('Reports', __('root.menu.reports'));
        $this->assertSame('Workspace', __('root.menu.workspace'));
        $this->assertSame('Sales', __('root.menu.sales'));
        $this->assertSame('Purchases & Expenses', __('root.menu.purchases_expenses'));
    }

    public function test_first_translation_batch_has_key_parity_and_preserves_placeholders()
    {
        $referenceKeys = null;

        foreach (self::BATCH_LOCALES as $locale) {
            $localeKeys = [];

            foreach (self::FILE_COUNTS as $file => $expectedCount) {
                $source = $this->flatten(require resource_path("lang/main/{$file}.php"));
                $translated = $this->flatten(require resource_path("lang/{$locale}/{$file}.php"));

                $this->assertCount($expectedCount, $translated, "{$locale}/{$file} translated keys");
                $this->assertSame([], array_diff_key($translated, $source), "{$locale}/{$file} must not add unknown keys");

                foreach ($translated as $key => $value) {
                    $this->assertIsString($value, "{$locale}/{$file}.{$key}");
                    $this->assertSame(
                        $this->placeholders($source[$key]),
                        $this->placeholders($value),
                        "{$locale}/{$file}.{$key} placeholders"
                    );
                    $localeKeys[$file . '.' . $key] = true;
                }
            }

            $keys = array_keys($localeKeys);
            sort($keys);

            if ($referenceKeys === null) {
                $referenceKeys = $keys;
            } else {
                $this->assertSame($referenceKeys, $keys, "{$locale} translated structure");
            }
        }
    }

    public function test_english_fallback_covers_intentionally_deferred_financial_keys()
    {
        app()->setLocale('fr');
        $this->assertSame('Supprimer', __('ui.actions.delete'));
        $this->assertSame('Revenu total', __('ui.dashboard.total_revenue'));
        $this->assertSame('Create invoice', __('header.create_invoice'));
        $this->assertSame('Trial Balance', __('root.menu.trial_balance'));
        $this->assertSame('custom-message', __('validation.custom.attribute-name.rule-name'));
    }

    public function test_language_selector_exposes_the_six_supported_locales()
    {
        config([
            'settings.company_name' => 'Localization Test',
            'settings.company_logo' => 'asset/img/logo.png',
            'view.compiled' => sys_get_temp_dir(),
        ]);
        app()->forgetInstance('blade.compiler');
        app()->setLocale('fr');

        $labels = LocaleManager::supportedLocales();
        $this->assertSame(
            ['English', 'Fran' . "\u{00E7}ais", "\u{0627}\u{0644}\u{0639}\u{0631}\u{0628}\u{064A}\u{0629}", 'Portugu' . "\u{00EA}s", 'Espa' . "\u{00F1}ol", "\u{7B80}\u{4F53}\u{4E2D}\u{6587}"],
            array_values($labels)
        );

        $header = file_get_contents(resource_path('views/shared/header.blade.php'));
        $this->assertStringContainsString('LocaleManager::supportedLocales()', $header);
        preg_match('/<select id="app-locale"[\s\S]*?<\/select>/', $header, $matches);
        $this->assertNotEmpty($matches, 'The supported-locale selector is present in the shared header.');
        $html = \Illuminate\Support\Facades\Blade::render($matches[0]);
        $this->assertMatchesRegularExpression('/value="fr"\s+selected/', $html);
        $this->withoutMiddleware(\App\Http\Middleware\Settings::class);
        $this->withSession(['app_locale' => 'main'])
            ->post(route('locale.update'), ['locale' => 'pt'])
            ->assertRedirect();
        $this->assertSame('pt', session('app_locale'));

        foreach (array_keys($labels) as $locale) {
            $this->assertStringContainsString('name="locale"', $html);
            $this->assertStringContainsString('value="' . $locale . '"', $html);
        }
    }

    public function test_login_and_password_reset_render_in_the_four_translated_locales_and_arabic_rtl()
    {
        $this->withoutMiddleware(\App\Http\Middleware\Settings::class);
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'settings.company_name' => 'AYU Test',
            'settings.company_logo' => 'asset/img/logo.png',
            'settings.company_color' => '#00897B',
            'view.compiled' => sys_get_temp_dir(),
        ]);
        app()->forgetInstance('blade.compiler');
        view()->share('errors', new ViewErrorBag());

        $expected = [
            'fr' => ['Se connecter', 'R' . "\u{00e9}" . 'initialiser le mot de passe'],
            'es' => ['Entrar', 'Restablecer contrase' . "\u{00f1}" . 'a'],
            'pt' => ['Entrar', 'Redefinir senha'],
            'zh' => ["\u{767b}\u{5f55}", "\u{91cd}\u{7f6e}\u{5bc6}\u{7801}"],
            'ar' => ["\u{062f}\u{062e}\u{0648}\u{0644}", "\u{0625}\u{0639}\u{0627}\u{062F}\u{0629}\u{0020}\u{062A}\u{0639}\u{064A}\u{064A}\u{0646}\u{0020}\u{0643}\u{0644}\u{0645}\u{0629}\u{0020}\u{0627}\u{0644}\u{0645}\u{0631}\u{0648}\u{0631}"],
        ];

        foreach ($expected as $locale => [$loginLabel, $resetLabel]) {
            $login = $this->withSession(['app_locale' => $locale])->get('/login');
            $login->assertOk()
                ->assertSee('lang="' . $locale . '"', false)
                ->assertSee('dir="' . ($locale === 'ar' ? 'rtl' : 'ltr') . '"', false)
                ->assertSee($loginLabel);

            $reset = $this->withSession(['app_locale' => $locale])->get('/password/reset/l06-render-test');
            $reset->assertOk()
                ->assertSee('lang="' . $locale . '"', false)
                ->assertSee('dir="' . ($locale === 'ar' ? 'rtl' : 'ltr') . '"', false)
                ->assertSee($resetLabel);

            if ($locale === 'ar') {
                $login->assertSee(asset('asset/css/style-rtl.css'), false);
                $reset->assertSee(asset('asset/css/style-rtl.css'), false);
            } else {
                $login->assertDontSee(asset('asset/css/style-rtl.css'), false);
                $reset->assertDontSee(asset('asset/css/style-rtl.css'), false);
            }
        }
    }

    public function test_main_layout_and_navigation_connect_arabic_to_rtl_assets()
    {
        config([
            'settings.company_name' => 'AYU Test',
            'settings.company_logo' => 'asset/img/logo.png',
            'settings.company_color' => '#00897B',
            'view.compiled' => sys_get_temp_dir(),
        ]);
        app()->forgetInstance('blade.compiler');
        app()->setLocale('ar');

        $html = view('layouts.app', ['is_rtl' => false])->render();
        $html = str_replace(["\r", "\n"], '', $html);
        $this->assertStringContainsString('<html lang="ar" dir="rtl">', $html);
        $this->assertStringContainsString(asset('asset/css/style-rtl.css'), $html);

        $header = file_get_contents(resource_path('views/shared/header.blade.php'));
        $sidebar = file_get_contents(resource_path('views/shared/sidebar.blade.php'));
        $this->assertStringContainsString("app()->getLocale() === 'ar'", $header);
        $this->assertStringContainsString('dir="rtl"', $header);
        $this->assertStringContainsString('<aside id="leftsidebar"', $sidebar);
    }
    public function test_verification_routes_remain_absent()
    {
        $this->assertFalse(Route::has('verification.resend'));
        $this->assertFalse(Route::has('verification.notice'));
        $this->assertFalse(Route::has('verification.verify'));
    }

    private function flatten(array $values, string $prefix = ''): array
    {
        $flat = [];

        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $flat += $this->flatten($value, $path);
            } else {
                $flat[$path] = $value;
            }
        }

        return $flat;
    }

    private function placeholders(string $value): array
    {
        preg_match_all('/:([A-Za-z_][A-Za-z0-9_]*)/', $value, $matches);
        $placeholders = $matches[1];
        sort($placeholders);

        return $placeholders;
    }
}

