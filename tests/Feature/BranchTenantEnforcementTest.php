<?php

namespace Tests\Feature;

use App\Branch;
use App\BusinessSetting;
use App\Services\TenantContext;
use App\Tenant;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class BranchTenantEnforcementTest extends TestCase
{
    use DatabaseTransactions;

    private const DATABASE = 'ayuglobal_saas_t01_test';
    private const MYSQL_USER = 'ayu_saas_t01_test_user@127.0.0.1';

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame(self::DATABASE, DB::connection()->getDatabaseName());
        $this->assertSame(self::MYSQL_USER, DB::selectOne('SELECT CURRENT_USER() AS user')->user);
        $this->assertSame(self::DATABASE, DB::selectOne('SELECT DATABASE() AS db')->db);

        $tenant = DB::table('tenants')->where('id', 1)->first();
        $this->assertNotNull($tenant);
        $this->assertSame('AYUGLOBAL', $tenant->name);
        $this->assertSame('ayuglobal', $tenant->slug);
        $this->assertSame('active', $tenant->status);

        $this->user = User::where('email', 'admin@gmail.com')->firstOrFail();
        config(['role_manage.Branch' => [
            'All' => 1, 'Create' => 1, 'Delete' => 1, 'Edit' => 1, 'Pdf' => 1,
            'PermanentlyDelete' => 1, 'Restore' => 1, 'Show' => 1, 'TrashShow' => 1,
        ]]);
        $this->forgetBranchCaches();
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        $this->forgetBranchCaches();
        Mockery::close();
        parent::tearDown();
    }

    public function test_tenant_one_lists_original_branch_ids_names_and_ownership(): void
    {
        $view = (new \App\Http\Controllers\BranchController())->index($this->requestForTenant());
        $this->assertSame([1, 2, 3, 4], $view->getData()['items']->sortBy('id')->pluck('id')->map(function ($id) {
            return (int) $id;
        })->all());
        $this->assertSame([1, 2, 3, 4], Branch::orderBy('id')->pluck('id')->map(function ($id) {
            return (int) $id;
        })->all());
        $this->assertSame(4, Branch::where('tenant_id', 1)->count());
    }

    public function test_branch_creation_uses_active_tenant_and_ignores_forged_tenant_id(): void
    {
        $response = $this->post(route('branch.store'), [
            'name' => 'T01D7D Temporary Test Branch',
            'location' => 'Test location',
            'description' => 'Transaction-scoped test record',
            'tenant_id' => 999999,
        ]);

        $response->assertRedirect();
        $branch = Branch::where('name', 'T01D7D Temporary Test Branch')->firstOrFail();
        $this->assertSame(1, (int) $branch->tenant_id);
    }

    public function test_branch_creation_is_blocked_at_the_business_override_limit(): void
    {
        BusinessSetting::where('tenant_id', 1)->update(['max_branches_override' => 4]);

        $response = $this->from(route('branch.create'))->post(route('branch.store'), [
            'name' => 'T01D7D Over-limit Branch',
        ]);

        $response->assertRedirect(route('branch.create'));
        $response->assertSessionHasErrors(['branches']);
        $this->assertSame(4, Branch::where('tenant_id', 1)->count());
    }

    public function test_branch_restore_is_blocked_when_it_would_exceed_the_limit(): void
    {
        BusinessSetting::where('tenant_id', 1)->update(['max_branches_override' => 4]);
        $branch = Branch::findOrFail(1);
        $branch->delete();

        $response = $this->post(route('branch.restore', ['id' => $branch->id]));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Restoring this branch would exceed your subscription branch limit.');
        $this->assertTrue(Branch::withTrashed()->findOrFail($branch->id)->trashed());
    }

    public function test_global_duplicate_branch_name_has_a_clear_validation_error(): void
    {
        $response = $this->from(route('branch.create'))->post(route('branch.store'), [
            'name' => 'Abuja Head Office',
        ]);

        $response->assertRedirect(route('branch.create'));
        $response->assertSessionHasErrors(['name']);
    }

    public function test_another_tenant_context_cannot_read_or_mutate_tenant_one_branch_ids(): void
    {
        $this->useSyntheticTenantContext(999999);

        $this->get('/branch/show/1')->assertNotFound();
        $this->get('/branch/edit/1')->assertNotFound();
        $this->get('/branch/pdf/1')->assertNotFound();
        $this->post('/branch/update/1', ['name' => 'Compromised'])->assertNotFound();
        $this->delete('/branch/destroy/1')->assertNotFound();
        $this->post('/branch/restore/1')->assertNotFound();
        $this->delete('/branch/kill/1')->assertNotFound();

        $this->assertSame('Abuja Head Office', Branch::findOrFail(1)->name);
        $this->assertSame(1, (int) Branch::findOrFail(1)->tenant_id);
    }

    public function test_missing_tenant_context_is_denied_before_branch_access(): void
    {
        $tenantContext = Mockery::mock(TenantContext::class);
        $tenantContext->shouldReceive('resolve')->andReturn([
            'tenant' => null,
            'eligible_tenants' => collect(),
            'selection_required' => false,
            'has_eligible_tenants' => false,
        ]);
        $this->app->instance(TenantContext::class, $tenantContext);

        $this->get(route('branch'))->assertForbidden();
    }

    public function test_lists_search_trash_and_pdf_are_tenant_scoped(): void
    {
        $this->useSyntheticTenantContext(999999);
        $controller = new \App\Http\Controllers\BranchController();
        $request = $this->requestForTenant();

        $this->assertCount(0, $controller->index($request)->getData()['items']);
        $searchRequest = $this->requestForTenant('/branch/active/search?search=Abuja');
        $this->assertCount(0, $controller->activeSearch($searchRequest)->getData()['items']);
        $this->assertCount(0, $controller->trashed($request)->getData()['items']);
        $trashedRequest = $this->requestForTenant('/branch/trashed/search?search=Abuja');
        $this->assertCount(0, $controller->trashedSearch($trashedRequest)->getData()['items']);
        $this->get('/branch/pdf/1')->assertNotFound();
    }

    public function test_bulk_actions_reject_mixed_or_cross_tenant_ids_without_partial_changes(): void
    {
        $response = $this->post(route('branch.active.action'), [
            'apply_comand_top' => 3,
            'items' => ['id' => [1, 999999]],
        ]);

        $response->assertNotFound();
        $this->assertNull(Branch::findOrFail(1)->deleted_at);
    }

    public function test_each_bulk_action_requires_its_own_branch_permission(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\RoleManage::class);
        config(['role_manage.Branch.Delete' => 1, 'role_manage.Branch.PermanentlyDelete' => 0]);
        $this->post(route('branch.active.action'), [
            'apply_comand_top' => 2,
            'items' => ['id' => [1]],
        ])->assertForbidden();

        config(['role_manage.Branch.Restore' => 0]);
        $this->post(route('branch.trashed.action'), [
            'apply_comand_top' => 1,
            'items' => ['id' => [1]],
        ])->assertForbidden();
    }

    public function test_soft_delete_and_restore_preserve_branch_name_and_id(): void
    {
        $branch = new Branch();
        $branch->tenant_id = 1;
        $branch->name = 'T01D7D Soft Delete Test Branch';
        $branch->save();

        $this->delete(route('branch.destroy', ['id' => $branch->id]))->assertRedirect();
        $trashed = Branch::withTrashed()->findOrFail($branch->id);
        $this->assertSame('T01D7D Soft Delete Test Branch', $trashed->name);
        $this->assertNotNull($trashed->deleted_at);

        $this->post(route('branch.restore', ['id' => $branch->id]))->assertRedirect();
        $restored = Branch::findOrFail($branch->id);
        $this->assertSame('T01D7D Soft Delete Test Branch', $restored->name);
        $this->assertSame((int) $branch->id, (int) $restored->id);
    }

    public function test_permanent_delete_refuses_existing_history_without_detaching_references(): void
    {
        $before = DB::table('inventories')->where('branch_id', 1)->count();

        $this->delete(route('branch.kill', ['id' => 1]))->assertRedirect();

        $this->assertSame('Abuja Head Office', Branch::findOrFail(1)->name);
        $this->assertSame(1, (int) Branch::findOrFail(1)->tenant_id);
        $this->assertSame($before, DB::table('inventories')->where('branch_id', 1)->count());
    }

    public function test_state_changing_branch_routes_do_not_accept_get(): void
    {
        foreach ([
            '/branch/destroy/1', '/branch/restore/1', '/branch/kill/1',
            '/branch/active/action', '/branch/trashed/action',
        ] as $path) {
            $this->get($path)->assertStatus(405);
        }
    }

    public function test_branch_routes_require_tenant_context_and_keep_operation_permissions(): void
    {
        $expected = [
            'branch' => ['tenant', 'branch.module_show'],
            'branch.store' => ['tenant', 'branch.create'],
            'branch.update' => ['tenant', 'branch.edit'],
            'branch.destroy' => ['tenant', 'branch.delete'],
            'branch.restore' => ['tenant', 'branch.restore'],
            'branch.kill' => ['tenant', 'branch.permanently_delete'],
            'branch.pdf' => ['tenant', 'branch.pdf'],
            'branch.active.search' => ['tenant', 'branch.module_show'],
            'branch.trashed.search' => ['tenant', 'branch.trash_show'],
        ];
        foreach ($expected as $name => $middleware) {
            $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, $name . ' route missing');
            foreach ($middleware as $required) {
                $this->assertContains($required, $route->gatherMiddleware(), $name);
            }
        }
        foreach (['branch.destroy', 'branch.kill'] as $name) {
            $this->assertContains('DELETE', \Illuminate\Support\Facades\Route::getRoutes()->getByName($name)->methods());
        }
        $this->assertContains('POST', \Illuminate\Support\Facades\Route::getRoutes()->getByName('branch.restore')->methods());
        $this->assertContains('POST', \Illuminate\Support\Facades\Route::getRoutes()->getByName('branch.active.action')->methods());
        $this->assertContains('POST', \Illuminate\Support\Facades\Route::getRoutes()->getByName('branch.trashed.action')->methods());
    }

    public function test_branch_caches_are_tenant_qualified_and_invalidated(): void
    {
        (new \App\Http\Controllers\BranchController())->index($this->requestForTenant());
        $this->assertSame(4, Cache::get('tenant:1:total_branches'));
        $this->assertFalse(Cache::has('total_branches'));

        $this->app->instance('request', $this->requestForTenant('/helper-test'));
        $shared = \App\Helpers\Helper::__getBranchBankCashIncomeExpenseHead();
        $this->assertCount(4, $shared['branches']);
        $this->assertTrue(Cache::has('tenant:1:branches'));
        $this->assertFalse(Cache::has('branches'));

        $branch = new Branch();
        $branch->tenant_id = 1;
        $branch->name = 'T01D7D Cache Test Branch';
        $branch->save();
        $this->post(route('branch.store'), ['name' => 'T01D7D Cache Invalidation Branch'])->assertRedirect();

        $this->assertFalse(Cache::has('tenant:1:total_branches'));
        $this->assertFalse(Cache::has('tenant:1:total_trashed_branches'));
        $this->assertFalse(Cache::has('tenant:1:branches'));
    }

    public function test_global_dashboard_is_restricted_to_tenant_one_until_its_data_is_tenant_safe(): void
    {
        $this->useSyntheticTenantContext(999999);

        $this->get('/')->assertForbidden();
        $this->get('/total-voucher/2025')->assertForbidden();
        $this->get('/profit-loss/2025')->assertForbidden();
    }

    public function test_branchless_references_remain_nullable(): void
    {
        foreach (['budgets', 'expenses', 'payrolls', 'inventories'] as $table) {
            $column = DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', self::DATABASE)
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', 'branch_id')
                ->first();
            $this->assertNotNull($column, $table . '.branch_id missing');
            $this->assertSame('YES', $column->IS_NULLABLE, $table . '.branch_id must remain nullable');
        }
    }

    private function useSyntheticTenantContext(int $tenantId): void
    {
        $tenant = new Tenant();
        $tenant->setRawAttributes([
            'id' => $tenantId,
            'name' => 'Synthetic test context',
            'slug' => 'synthetic-test-context',
            'status' => 'active',
        ]);

        $tenantContext = Mockery::mock(TenantContext::class);
        $tenantContext->shouldReceive('resolve')->andReturn([
            'tenant' => $tenant,
            'eligible_tenants' => collect([$tenant]),
            'selection_required' => false,
            'has_eligible_tenants' => true,
        ]);
        $tenantContext->shouldReceive('currentTenant')->andReturn($tenant);
        $this->app->instance(TenantContext::class, $tenantContext);
    }

    private function forgetBranchCaches(): void
    {
        foreach ([1, 999999] as $tenantId) {
            foreach (['branches', 'total_branches', 'total_trashed_branches'] as $key) {
                Cache::forget('tenant:' . $tenantId . ':' . $key);
            }
        }

        foreach (['branches', 'total_branches', 'total_trashed_branches'] as $key) {
            Cache::forget($key);
        }
    }

    private function requestForTenant(string $uri = '/branch'): \Illuminate\Http\Request
    {
        $request = \Illuminate\Http\Request::create($uri, 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        return $request;
    }
}
