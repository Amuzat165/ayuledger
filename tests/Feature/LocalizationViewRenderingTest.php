<?php

namespace Tests\Feature;

use Illuminate\Auth\GenericUser;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class LocalizationViewRenderingTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_authenticated_nonfinancial_views_render_with_isolated_in_memory_data()
    {
        app()->setLocale('main');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        config(['settings.company_name' => 'AYU Test', 'settings.company_logo' => 'asset/img/logo.png', 'settings.company_color' => '#00897B']);
        config(['view.compiled' => sys_get_temp_dir()]);
        app()->forgetInstance('blade.compiler');
        $this->withoutMiddleware(\App\Http\Middleware\Settings::class);
        view()->share('is_rtl', false);
        view()->share('errors', new ViewErrorBag());

        $this->get('/login')->assertOk()->assertSee('Log in');
        $this->get('/register')->assertRedirect(route('login'));
        $this->assertRenderedView('auth.register', [], __('auth.register'));
        $this->assertRenderedView('auth.passwords.email', [], __('auth.send_password_reset_link'));
        $this->get('/password/reset/render-test-token')->assertOk()->assertSee(__('auth.reset_password'));
        $this->assertFalse(Route::has('verification.resend'));

        auth()->setUser(new GenericUser(['id' => 9001, 'name' => 'Render Test', 'email' => 'render@example.test']));

        $roles = collect([(object) ['id' => 1, 'name' => 'Test Administrator']]);
        $renderUser = (object) [
            'id' => 9001, 'name' => 'Render Test', 'email' => 'render@example.test',
            'role' => (object) ['name' => 'Test Administrator'],
        ];
        $roleQuery = Mockery::mock();
        $roleQuery->shouldReceive('get')->andReturn($roles);
        $roleQuery->shouldReceive('count')->andReturn(0);
        $roleModel = Mockery::mock('alias:App\\RoleManage');
        $roleModel->shouldReceive('all')->andReturn($roles);
        $roleModel->shouldReceive('onlyTrashed')->andReturn($roleQuery);
        $roleModel->shouldReceive('orderBy')->andReturn($roleQuery);

        $userQuery = Mockery::mock();
        $userQuery->shouldReceive('count')->andReturn(0);
        $userModel = Mockery::mock('alias:App\\User');
        $userModel->shouldReceive('all')->andReturn(collect([$renderUser]));
        $userModel->shouldReceive('onlyTrashed')->andReturn($userQuery);
        $userModel->shouldReceive('find')->andReturn((object) ['role' => ['name' => 'Administrator']]);

        $profile = [
            'id' => 2, 'user_id' => 9001, 'avatar' => null, 'first_name' => 'Render', 'last_name' => 'Test',
            'phone_number' => '555-0100', 'designation' => 'Administrator', 'gender' => 1, 'education' => '',
            'permanent_address' => '', 'present_address' => '', 'description' => '',
        ];
        $profileHtml = $this->assertRenderedView('admin.profile.index', ['profile' => $profile, 'languages' => collect(), 'language_id' => null], __('ui.profile.profile_settings'));
        $this->assertStringContainsString('name="first_name"', $profileHtml);
        $this->assertStringContainsString('<h3>Test Render</h3>', $profileHtml);

        $generalSettings = array_merge(config('settings'), [
            'company_name' => 'L05 Render Test', 'contract_person' => 'Test Contact', 'email' => 'render@example.test',
            'phone' => '555-0100', 'address_1' => 'Test Address', 'address_2' => '', 'city' => 'Test City',
            'state' => 'Test State', 'zip_code' => '00000', 'company_logo' => 'asset/img/logo.png',
            'company_color' => '#00897B', 'header_image' => null, 'vat_percentage' => 0,
            'wht_enabled' => false, 'wht_mode' => 'manual', 'wht_manual_rate' => '0.00', 'discount_enabled' => false,
        ]);
        $generalHtml = $this->assertRenderedView('admin.settings.general', ['settings' => $generalSettings], __('ui.settings.company_name'));
        $this->assertStringContainsString('name="company_name"', $generalHtml);
        $systemSettings = array_merge(config('settings'), [
            'date_format' => 'd m Y', 'timezone' => 'Africa/Lagos', 'currency_code' => 'NGN', 'currency_symbol' => '₦',
            'is_code' => 'code', 'currency_position' => 'Prefix', 'fixed_asset_schedule_starting_date' => '2026-01-01',
            'is_rtl' => 'No',
        ]);
        $systemHtml = $this->assertRenderedView('admin.settings.system', ['settings' => $systemSettings], __('ui.settings.date_format'));
        $this->assertStringContainsString('name="date_format"', $systemHtml);

        $userPage = new LengthAwarePaginator([$renderUser], 1, 60, 1);
        $this->assertRenderedView('admin.user.index', ['items' => $userPage], 'render@example.test');
        $userCreateHtml = $this->assertRenderedView('admin.user.create', [], __('ui.forms.email_address'));
        $this->assertStringContainsString('name="email"', $userCreateHtml);
        $this->assertStringContainsString('name="password_confirmation"', $userCreateHtml);
        $user = (object) ['id' => 9, 'name' => 'Render Test', 'email' => 'render@example.test', 'role_manage_id' => 1];
        $this->assertRenderedView('admin.user.edit', ['item' => $user], __('ui.forms.email_address'));
        $userPermissions = json_decode(json_encode(['User' => ['User', 1, 1, 1, 0, 0, 0, 0, 0, 0]]));
        $this->assertRenderedView('admin.user.show', ['items' => (object) ['name' => 'Render Test', 'content' => $userPermissions]], __('ui.permissions.module_name'));

        $rolePage = new LengthAwarePaginator($roles, 1, 60, 1);
        $this->assertRenderedView('admin.role-manage.index', ['items' => $rolePage], 'Test Administrator');
        $this->assertRenderedView('admin.role-manage.create', [], __('ui.permissions.module_name'));
        $permissions = json_decode(json_encode(['User' => ['User', 1, 1, 1, 0, 0, 0, 0, 0, 0]]));
        $this->assertRenderedView('admin.role-manage.edit', ['item' => (object) ['id' => 1, 'name' => 'Test Administrator', 'content' => $permissions]], __('ui.permissions.module_name'));
        $this->assertRenderedView('admin.role-manage.show', ['items' => (object) ['name' => 'Test Administrator', 'content' => $permissions]], __('ui.permissions.module_name'));
    }

    private function assertRenderedView(string $name, array $data, string $expectedText): string
    {
        $html = view($name, $data)->render();

        $this->assertStringContainsString($expectedText, $html, $name);
        $this->assertStringNotContainsString('Translation missing', $html, $name);
        $this->assertStringContainsString('<form', $html, $name);

        return $html;
    }
}
