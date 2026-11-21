<?php

namespace Tests\Unit;

use App\Http\Middleware\RoleManage;
use App\RoleManage as RoleManageModel;
use App\Services\TenantContext;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RoleManageFailClosedPermissionsTest extends TestCase
{
    private $modules = [
        'Branch' => ['All' => 1, 'Show' => 1, 'Create' => 1, 'Edit' => 1, 'Delete' => 1],
        'Inventory' => ['All' => 0, 'Show' => 0, 'Create' => 0, 'Edit' => 0, 'Delete' => 0],
    ];

    public function test_missing_role_contents_deny_every_configured_module_permission(): void
    {
        $resolved = RoleManage::permissionsFromRoleContents([], $this->modules);

        foreach ($resolved as $value) {
            $this->assertSame(0, $value);
        }
    }

    public function test_valid_stored_integer_flags_preserve_their_permissions(): void
    {
        $resolved = RoleManage::permissionsFromRoleContents([
            'Branch' => [0 => 'Branch', 1 => 1, 2 => 0, 3 => 1, 4 => 1, 5 => 0],
            'Inventory' => [0 => 'Inventory', 1 => 0, 3 => 1, 4 => 0, 5 => 1],
        ], $this->modules);

        $this->assertSame(1, $resolved['role_manage.Branch.All']);
        $this->assertSame(0, $resolved['role_manage.Branch.Show']);
        $this->assertSame(1, $resolved['role_manage.Branch.Create']);
        $this->assertSame(1, $resolved['role_manage.Branch.Edit']);
        $this->assertSame(0, $resolved['role_manage.Branch.Delete']);
        $this->assertSame(0, $resolved['role_manage.Inventory.All']);
        $this->assertSame(1, $resolved['role_manage.Inventory.Create']);
        $this->assertSame(0, $resolved['role_manage.Inventory.Edit']);
        $this->assertSame(1, $resolved['role_manage.Inventory.Delete']);
    }

    public function test_missing_modules_indexes_and_non_integer_flags_deny_access(): void
    {
        $resolved = RoleManage::permissionsFromRoleContents([
            'Branch' => [0 => 'Branch', 1 => 1, 3 => 'yes', 4 => '1', 5 => 2],
            'Inventory' => 'malformed',
        ], $this->modules);

        $this->assertSame(1, $resolved['role_manage.Branch.All']);
        $this->assertSame(0, $resolved['role_manage.Branch.Show']);
        $this->assertSame(0, $resolved['role_manage.Branch.Create']);
        $this->assertSame(0, $resolved['role_manage.Branch.Edit']);
        $this->assertSame(0, $resolved['role_manage.Branch.Delete']);
        $this->assertSame(0, $resolved['role_manage.Inventory.All']);
        $this->assertSame(0, $resolved['role_manage.Inventory.Create']);
    }

    public function test_middleware_denies_permissions_when_role_lookup_returns_no_record(): void
    {
        $user = new User();
        $user->role_manage_id = 123;
        Auth::guard()->setUser($user);

        $middleware = new class(app(TenantContext::class)) extends RoleManage
        {
            protected function findRole($roleId): ?RoleManageModel
            {
                return null;
            }
        };

        $response = $middleware->handle(
            Request::create('/inventory'),
            function () {
                return new Response('continued');
            }
        );

        $this->assertSame('continued', $response->getContent());
        $this->assertSame(0, config('role_manage.Branch.All'));
        $this->assertSame(0, config('role_manage.Inventory.All'));
    }

    public function test_tenant_scoped_request_uses_membership_role_instead_of_global_role(): void
    {
        $user = new User();
        $user->role_manage_id = 2;
        Auth::guard()->setUser($user);
        config(['role_manage' => ['Branch' => []]]);

        $context = new StubTenantContextForRoleManage([
            'tenant' => new \App\Tenant(),
            'membership' => ['role_manage_id' => 9],
        ]);
        $middleware = new InspectingRoleManage($context);

        $middleware->handle($this->requestWithMiddleware('tenant'), function () {
            return new Response('continued');
        });

        $this->assertSame([9], $middleware->lookedUpRoleIds);
        $this->assertSame(1, config('role_manage.Branch.All'));
    }

    public function test_tenant_scoped_request_fails_closed_for_missing_null_or_invalid_membership_role(): void
    {
        $user = new User();
        $user->role_manage_id = 2;
        Auth::guard()->setUser($user);
        config(['role_manage' => ['Branch' => []]]);

        foreach ([null, [], ['role_manage_id' => null], ['role_manage_id' => 0], ['role_manage_id' => 'invalid']] as $membership) {
            config(['role_manage.Branch.All' => 1]);
            $context = new StubTenantContextForRoleManage([
                'tenant' => new \App\Tenant(),
                'membership' => $membership,
            ]);
            $middleware = new InspectingRoleManage($context);

            $middleware->handle($this->requestWithMiddleware('tenant'), function () {
                return new Response('continued');
            });

            $this->assertSame([null], $middleware->lookedUpRoleIds);
            $this->assertSame(0, config('role_manage.Branch.All'));
        }

        // A syntactically valid but nonexistent membership role also denies;
        // the global role is never consulted as a fallback.
        config(['role_manage.Branch.All' => 1]);
        $context = new StubTenantContextForRoleManage([
            'tenant' => new \App\Tenant(),
            'membership' => ['role_manage_id' => 99],
        ]);
        $middleware = new InspectingRoleManage($context);
        $middleware->handle($this->requestWithMiddleware('tenant'), function () {
            return new Response('continued');
        });

        $this->assertSame([99], $middleware->lookedUpRoleIds);
        $this->assertSame(0, config('role_manage.Branch.All'));
    }

    public function test_unscoped_request_retains_global_role_behavior(): void
    {
        $user = new User();
        $user->role_manage_id = 2;
        Auth::guard()->setUser($user);
        config(['role_manage' => ['Branch' => []]]);

        $context = new StubTenantContextForRoleManage([]);
        $middleware = new InspectingRoleManage($context);

        $middleware->handle($this->requestWithMiddleware(), function () {
            return new Response('continued');
        });

        $this->assertSame([2], $middleware->lookedUpRoleIds);
        $this->assertSame(0, config('role_manage.Branch.All'));
        $this->assertSame(0, $context->resolveCalls);
    }

    private function requestWithMiddleware(?string $middleware = null): Request
    {
        $action = $middleware ? ['middleware' => [$middleware]] : [];
        $route = new Route(['GET'], '/role-check', $action);
        $request = Request::create('/role-check');
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        return $request;
    }
}

class StubTenantContextForRoleManage extends TenantContext
{
    private $resolution;
    public $resolveCalls = 0;

    public function __construct(array $resolution)
    {
        $this->resolution = $resolution;
    }

    public function resolve(Request $request, ?User $user = null): array
    {
        $this->resolveCalls++;

        return $this->resolution;
    }
}

class InspectingRoleManage extends RoleManage
{
    public $lookedUpRoleIds = [];

    protected function findRole($roleId): ?RoleManageModel
    {
        $this->lookedUpRoleIds[] = $roleId;
        if ($roleId !== 9) {
            return null;
        }

        $role = new RoleManageModel();
        $role->content = json_encode(['Branch' => [0 => 'Branch', 1 => 1]]);

        return $role;
    }
}
