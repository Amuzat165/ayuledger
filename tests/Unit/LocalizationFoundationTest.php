<?php

namespace Tests\Unit;

use App\Support\LocaleManager;
use App\Http\Controllers\LocaleController;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LocalizationFoundationTest extends TestCase
{
    public function test_initial_supported_locales_are_registered()
    {
        $this->assertSame(
            ['main', 'fr', 'ar', 'pt', 'es', 'zh'],
            array_keys(LocaleManager::supportedLocales())
        );
    }

    public function test_locale_aliases_are_normalized()
    {
        $this->assertSame('main', LocaleManager::normalize('eng'));
        $this->assertSame('main', LocaleManager::normalize('en'));
        $this->assertSame('zh', LocaleManager::normalize('zh-CN'));
        $this->assertSame('fr', LocaleManager::normalize('fr'));
    }

    public function test_invalid_locale_is_not_accepted_and_default_is_english()
    {
        $this->assertNull(LocaleManager::normalize('../ar'));
        $this->assertNull(LocaleManager::normalize('unsupported'));
        $this->assertSame('main', LocaleManager::DEFAULT_LOCALE);
    }

    public function test_arabic_is_rtl_and_other_initial_locales_are_ltr()
    {
        $this->assertSame('rtl', LocaleManager::direction('ar'));
        $this->assertSame('ltr', LocaleManager::direction('fr'));
        $this->assertSame('ltr', LocaleManager::direction('zh'));
    }
    public function test_locale_selection_persists_through_session_and_is_applied_next_request()
    {
        $session = new Store('locale-test', new ArraySessionHandler(120));
        $request = Request::create('/locale', 'POST', ['locale' => 'pt']);
        $request->setLaravelSession($session);

        (new LocaleController())->update($request);
        $this->assertSame('pt', $session->get('app_locale'));

        $nextRequest = Request::create('/dashboard');
        $nextRequest->setLaravelSession($session);
        (new SetLocale())->handle($nextRequest, function () {
            return response('ok');
        });

        $this->assertSame('pt', app()->getLocale());
    }

    public function test_invalid_session_locale_falls_back_to_english()
    {
        $session = new Store('locale-test', new ArraySessionHandler(120));
        $session->put('app_locale', '../invalid');
        $request = Request::create('/dashboard');
        $request->setLaravelSession($session);

        (new SetLocale())->handle($request, function () {
            return response('ok');
        });

        $this->assertSame('main', app()->getLocale());
    }
    public function test_extracted_header_strings_resolve_from_main_and_legacy_eng_falls_back()
    {
        app()->setLocale('main');
        $this->assertSame('Quick create', __('header.quick_create'));
        $this->assertSame('Add vendor', __('header.add_vendor'));
        $this->assertSame('Home', __('root.common.home'));
        $this->assertSame('Search', trim(__('root.common.search')));

        app()->setLocale('eng');
        $this->assertSame('Quick create', __('header.quick_create'));
        app()->setLocale('main');
    }
    public function test_l03_english_ui_strings_resolve_and_sidebar_dashboard_templates_compile()
    {
        app()->setLocale('main');

        $this->assertSame('Initial Balance', __('ui.sidebar.initial_balance'));
        $this->assertSame('Total Revenue', __('ui.dashboard.total_revenue'));
        $this->assertSame('Please wait...', __('ui.common.please_wait'));

        app()->setLocale('eng');
        $this->assertSame('Total Revenue', __('ui.dashboard.total_revenue'));
        app()->setLocale('main');

        $compiler = app('blade.compiler');
        $sidebar = $compiler->compileString(file_get_contents(resource_path('views/shared/sidebar.blade.php')));
        $dashboard = $compiler->compileString(file_get_contents(resource_path('views/admin/dashboard/index.blade.php')));
        $layout = $compiler->compileString(file_get_contents(resource_path('views/layouts/app.blade.php')));

        $this->assertStringContainsString("route('administration.hub')", $sidebar);
        $this->assertStringContainsString("route('reports.accounts.trial_balance')", $dashboard);
        $this->assertStringContainsString("__('ui.dashboard.total_revenue')", $dashboard);
        $this->assertStringContainsString("__('ui.common.please_wait')", $layout);
    }
    public function test_l04_common_forms_messages_and_auth_templates_compile()
    {
        app()->setLocale('main');
        config(['view.compiled' => sys_get_temp_dir()]);
        app()->forgetInstance('blade.compiler');

        $this->assertSame('No records found', __('ui.messages.no_records_found'));
        $this->assertSame('You cannot perform this action. Please contact your IT officer.', __('ui.messages.permission_denied'));
        $this->assertSame('Put User Information', __('ui.forms.put_module_information', ['module' => 'User']));
        $this->assertSame('Do you want to Trash?', __('ui.messages.confirm_trash'));
        $this->assertSame('Enter your account details to continue.', __('ui.auth.enter_account_details'));

        $this->withoutMiddleware(\App\Http\Middleware\Settings::class);
        $login = $this->get('/login');
        $login->assertOk();
        $login->assertSee('Log in');
        $login->assertSee('placeholder="Email address"', false);

        $search = \Illuminate\Support\Facades\Blade::render('<x-table.table-search-bar :all="$all" :trash="$trash" />', ['all' => 3, 'trash' => 1]);
        $this->assertStringContainsString('All (3)', $search);
        $emptyState = \Illuminate\Support\Facades\Blade::render('<x-table.table-no-records />');
        $this->assertStringContainsString('No records found', $emptyState);
        $actions = \Illuminate\Support\Facades\Blade::render('<x-table.table-action :item="$item" :actions="$actions" />', ['item' => null, 'actions' => ['show', 'edit', 'delete', 'export']]);
        $this->assertStringContainsString('title="Show"', $actions);
        $this->assertStringContainsString('title="Delete"', $actions);


        $layoutHtml = view('layouts.app', ['is_rtl' => false])->render();
        $this->assertStringContainsString('confirmPermanentDelete', $layoutHtml);
        $this->assertStringContainsString('Do you want to permanently delete?', $layoutHtml);

        $compiler = app('blade.compiler');
        foreach ([
            'components/table/table-search-bar.blade.php',
            'components/table/table-no-records.blade.php',
            'components/table/table-action.blade.php',
            'components/table/table-pagination-with-selected.blade.php',
            'layouts/app.blade.php',
            'admin/user/create.blade.php',
            'admin/user/edit.blade.php',
            'auth/login.blade.php',
        ] as $view) {
            $compiled = $compiler->compileString(file_get_contents(resource_path('views/' . $view)));
            $this->assertStringNotContainsString('Translation missing', $compiled, $view);
        }
    }

    public function test_l05_nonfinancial_admin_auth_and_javascript_strings_resolve_and_compile()
    {
        app()->setLocale('main');

        $this->assertSame('Company Name', __('ui.settings.company_name'));
        $this->assertSame('Change Password', __('ui.profile.change_password'));
        $this->assertSame('No records found', __('ui.messages.no_records_found'));
        $this->assertSame('Permanently Delete?', __('ui.messages.permanently_delete'));
        $this->assertSame('Module Name', __('ui.permissions.module_name'));
        $this->assertSame('Secure sign-in for your finance and reporting workspace.', __('ui.login.workspace_summary'));
        $this->assertSame('Yes', __('ui.common.yes'));
        $this->assertSame('No', __('ui.common.no'));
        $this->assertSame('Register', __('auth.register'));
        $this->assertSame('Reset Password', __('auth.reset_password'));

        $layoutHtml = view('layouts.app', ['is_rtl' => false])->render();
        $this->assertStringContainsString('networkError:', $layoutHtml);
        $this->assertStringContainsString('Network error', $layoutHtml);

        $helperScript = file_get_contents(public_path('asset/js/helpers.js'));
        $this->assertStringContainsString('window.ayuUiMessages.networkError', $helperScript);
        $this->assertStringNotContainsString("'Network error'", $helperScript);

        $compiler = app('blade.compiler');
        foreach ([
            'admin/profile/index.blade.php',
            'admin/settings/general.blade.php',
            'admin/settings/system.blade.php',
            'admin/user/index.blade.php',
            'admin/user/trashed.blade.php',
            'admin/role-manage/index.blade.php',
            'admin/role-manage/trashed.blade.php',
            'admin/role-manage/pdf.blade.php',
            'auth/login.blade.php',
            'auth/register.blade.php',
            'auth/passwords/email.blade.php',
            'auth/passwords/reset.blade.php',
        ] as $view) {
            $compiled = $compiler->compileString(file_get_contents(resource_path('views/' . $view)));
            $this->assertStringNotContainsString('Translation missing', $compiled, $view);
        }
    }
}
