<?php

namespace Tests\Feature;

use App\Support\LocaleManager;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class ArabicLocalizationTest extends TestCase
{
    private const FILE_COUNTS = [
        'aside-lang' => 8, 'auth' => 6, 'header' => 9, 'pagination' => 2,
        'passwords' => 5, 'root' => 213, 'ui' => 117, 'financial' => 445, 'validation' => 84,
    ];

    public function test_arabic_safe_ui_keys_are_complete_and_placeholders_match()
    {
        $this->assertCount(1279, $this->flattenAll('main'));
        $total = 0;
        $missingByFile = [];
        foreach (self::FILE_COUNTS as $file => $expectedCount) {
            $source = $this->flatten(require resource_path("lang/main/{$file}.php"));
            $arabic = $this->flatten(require resource_path("lang/ar/{$file}.php"));
            $this->assertCount($expectedCount, $arabic, "Arabic {$file} key count");
            $this->assertSame([], array_diff_key($arabic, $source));
            foreach ($arabic as $key => $value) {
                $this->assertIsString($value, "ar/{$file}.{$key}");
                $this->assertSame($this->placeholders($source[$key]), $this->placeholders($value), "ar/{$file}.{$key}");
            }
            $missingByFile[$file] = count(array_diff_key($source, $arabic));
            $total += count($arabic);
        }
        $this->assertSame(889, $total);
        $this->assertSame([
            'aside-lang' => 16, 'auth' => 0, 'header' => 4, 'pagination' => 0,
            'passwords' => 0, 'root' => 169, 'ui' => 6, 'financial' => 0, 'validation' => 0,
        ], $missingByFile);
        $this->assertSame(['ui.permissions.pdf'], $this->sameAsEnglishKeys());
    }

    public function test_arabic_loads_and_financial_keys_fall_back_to_english()
    {
        app()->setLocale('ar');
        $this->assertSame("\u{062f}\u{062e}\u{0648}\u{0644}", __('ui.auth.login'));
        $this->assertSame("\u{0627}\u{0644}\u{0625}\u{062F}\u{0627}\u{0631}\u{0629}", __('root.menu.administration'));
        $this->assertSame("\u{0625}\u{062C}\u{0645}\u{0627}\u{0644}\u{064A}\u{0020}\u{0627}\u{0644}\u{0625}\u{064A}\u{0631}\u{0627}\u{062F}\u{0627}\u{062A}", __('ui.dashboard.total_revenue'));
        $this->assertSame('Create invoice', __('header.create_invoice'));
        $this->assertSame('Trial Balance', __('root.menu.trial_balance'));
        $this->assertSame('Accounting', __('root.menu.accounting'));
        $this->assertSame('Banking & Cash', __('root.menu.banking_cash'));
        $this->assertSame('Inventory & Assets', __('root.menu.inventory_assets'));
    }

    public function test_login_and_reset_pages_render_arabic_rtl_and_english_ltr()
    {
        $this->withoutMiddleware(\App\Http\Middleware\Settings::class);
        config([
            'settings.company_name' => 'AYU Test',
            'settings.company_logo' => 'asset/img/logo.png',
            'settings.company_color' => '#00897B',
            'view.compiled' => sys_get_temp_dir(),
        ]);
        app()->forgetInstance('blade.compiler');
        $errors = new ViewErrorBag();
        $errors->put('default', new MessageBag(['email' => [__('validation.required', ['attribute' => __('ui.auth.email_address')])]]));
        view()->share('errors', $errors);

        foreach (['ar' => 'rtl', 'main' => 'ltr'] as $locale => $direction) {
            $login = $this->withSession(['app_locale' => $locale])->get('/login');
            $login->assertOk()->assertSee('lang="' . $locale . '"', false)->assertSee('dir="' . $direction . '"', false);
            $reset = $this->withSession(['app_locale' => $locale])->get('/password/reset/l07-render-test');
            $reset->assertOk()->assertSee('lang="' . $locale . '"', false)->assertSee('dir="' . $direction . '"', false);
            if ($locale === 'ar') {
                $login->assertSee("\u{062F}\u{062E}\u{0648}\u{0644}")->assertSee(asset('asset/css/style-rtl.css'), false);
                $reset->assertSee("\u{0625}\u{0639}\u{0627}\u{062F}\u{0629}\u{0020}\u{062A}\u{0639}\u{064A}\u{064A}\u{0646}\u{0020}\u{0643}\u{0644}\u{0645}\u{0629}\u{0020}\u{0627}\u{0644}\u{0645}\u{0631}\u{0648}\u{0631}")->assertSee(asset('asset/css/style-rtl.css'), false);
            } else {
                $login->assertSee('Log in')->assertDontSee(asset('asset/css/style-rtl.css'), false);
                $reset->assertSee('Reset Password')->assertDontSee(asset('asset/css/style-rtl.css'), false);
            }
        }
    }

    public function test_arabic_validation_alert_renders_in_a_form()
    {
        config([
            'settings.company_name' => 'AYU Test',
            'settings.company_logo' => 'asset/img/logo.png',
            'view.compiled' => sys_get_temp_dir(),
        ]);
        app()->forgetInstance('blade.compiler');
        app()->setLocale('ar');

        $errors = new ViewErrorBag();
        $errors->put('default', new MessageBag([
            'email' => [__('validation.required', ['attribute' => __('ui.auth.email_address')])],
        ]));
        view()->share('errors', $errors);

        $html = view('auth.login')->render();
        $this->assertStringContainsString("\u{062D}\u{0642}\u{0644}\u{0020}\u{0639}\u{0646}\u{0648}\u{0627}\u{0646}\u{0020}\u{0627}\u{0644}\u{0628}\u{0631}\u{064A}\u{062F}\u{0020}\u{0627}\u{0644}\u{0625}\u{0644}\u{0643}\u{062A}\u{0631}\u{0648}\u{0646}\u{064A}\u{0020}\u{0645}\u{0637}\u{0644}\u{0648}\u{0628}\u{002E}", $html);
        $this->assertStringContainsString('is-invalid', $html);
        $this->assertStringContainsString('dir="rtl"', $html);
    }
    public function test_application_layout_uses_arabic_rtl_and_returns_to_english_ltr()
    {
        config([
            'settings.company_name' => 'AYU Test',
            'settings.company_logo' => 'asset/img/logo.png',
            'settings.company_color' => '#00897B',
            'view.compiled' => sys_get_temp_dir(),
        ]);
        app()->forgetInstance('blade.compiler');
        app()->setLocale('ar');
        $arabic = view('layouts.app', ['is_rtl' => false])->render();
        $this->assertStringContainsString('<html lang="ar" dir="rtl">', str_replace(["\r", "\n"], '', $arabic));
        $this->assertStringContainsString(asset('asset/css/style-rtl.css'), $arabic);
        app()->setLocale('main');
        $english = view('layouts.app', ['is_rtl' => false])->render();
        $this->assertStringContainsString('<html lang="main" dir="ltr">', str_replace(["\r", "\n"], '', $english));
        $this->assertStringNotContainsString(asset('asset/css/style-rtl.css'), $english);
        $this->assertSame('rtl', LocaleManager::direction('ar'));
        $this->assertSame('ltr', LocaleManager::direction('main'));
    }

    public function test_arabic_search_and_bulk_pagination_controls_render()
    {
        config(['view.compiled' => sys_get_temp_dir()]);
        app()->forgetInstance('blade.compiler');
        app()->setLocale('ar');

        $search = \Illuminate\Support\Facades\Blade::render('<x-table.table-search-bar :all="12" :trash="0" />');
        $this->assertStringContainsString("\u{0627}\u{0644}\u{0643}\u{0644}\u{0020}\u{0028}\u{0031}\u{0032}\u{0029}", $search);
        $this->assertStringContainsString("\u{0627}\u{0644}\u{0645}\u{062D}\u{0630}\u{0648}\u{0641}\u{0627}\u{062A}\u{0020}\u{0028}\u{0030}\u{0029}", $search);
        $this->assertStringContainsString('placeholder="' . "\u{0628}\u{062D}\u{062B}\u{0020}\u{0028}\u{0020}\u{002F}\u{0020}\u{0029}" . '"', $search);

        $pagination = \Illuminate\Support\Facades\Blade::render(
            '<x-table.table-pagination-with-selected><ul><li>' . __('pagination.next') . '</li></ul></x-table.table-pagination-with-selected>'
        );
        $this->assertStringContainsString("\u{627}\u{644}\u{625}\u{62c}\u{631}\u{627}\u{621}\u{20}\u{644}\u{644}\u{639}\u{646}\u{627}\u{635}\u{631}\u{20}\u{627}\u{644}\u{645}\u{62d}\u{62f}\u{62f}\u{629}\u{3a}", $pagination);
        $this->assertStringContainsString("\u{62a}\u{637}\u{628}\u{64a}\u{642}", $pagination);
        $this->assertStringContainsString("\u{627}\u{644}\u{62a}\u{627}\u{644}\u{64a}", $pagination);
    }
    private function flattenAll(string $locale): array
    {
        $flat = [];
        foreach (glob(resource_path("lang/{$locale}/*.php")) as $file) {
            $flat += $this->flatten(require $file);
        }
        return $flat;
    }

    private function flatten(array $values, string $prefix = ''): array
    {
        $flat = [];
        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) $flat += $this->flatten($value, $path);
            else $flat[$path] = $value;
        }
        return $flat;
    }

    private function placeholders(string $value): array
    {
        preg_match_all('/:([A-Za-z_][A-Za-z0-9_]*)/', $value, $matches);
        $placeholders = array_values(array_unique($matches[1]));
        sort($placeholders);
        return $placeholders;
    }

    private function sameAsEnglishKeys(): array
    {
        $same = [];
        foreach (glob(resource_path('lang/main/*.php')) as $file) {
            $name = basename($file);
            $source = $this->flatten(require $file);
            $target = resource_path("lang/ar/{$name}");
            if (!is_file($target)) continue;
            foreach ($this->flatten(require $target) as $key => $value) {
                if (isset($source[$key]) && $source[$key] === $value) {
                    $same[] = ($name === 'ui.php' ? 'ui.' : $name . '.') . $key;
                }
            }
        }
        sort($same);
        return $same;
    }
}
