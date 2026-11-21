<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RequireTenantContext;
use App\Http\Middleware\RoleManage;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\Settings;
use App\Services\TenantAccessRepository;
use App\Services\TenantContext;
use App\Tenant;
use App\TenantMembership;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class FakeTenantAccessRepository extends TenantAccessRepository
{
    public $eligible = [];
    public $selectable = [];
    public $memberships = [];

    public function eligibleTenants(User $user): EloquentCollection
    {
        return new EloquentCollection($this->eligible);
    }

    public function findEligibleTenant(User $user, $tenantId): ?Tenant
    {
        $userId = (int) $user->getAuthIdentifier();
        $tenantId = (int) $tenantId;
        $tenant = $this->selectable[$userId][$tenantId] ?? null;

        if (!$tenant || $tenant->status !== 'active') {
            return null;
        }

        return $this->activeMemberships($userId, $tenantId) !== []
            ? $tenant
            : null;
    }

    public function findEligibleMembership(User $user, Tenant $tenant): ?TenantMembership
    {
        $userId = (int) $user->getAuthIdentifier();
        $tenantId = (int) $tenant->getKey();

        if ($tenant->status !== 'active') {
            return null;
        }

        $matches = $this->activeMemberships($userId, $tenantId);

        return count($matches) === 1 ? $matches[0] : null;
    }

    private function activeMemberships(int $userId, int $tenantId): array
    {
        $memberships = $this->memberships[$userId][$tenantId] ?? [];

        return array_values(array_filter($memberships, function (TenantMembership $membership) use ($userId, $tenantId) {
            return (int) $membership->user_id === $userId
                && (int) $membership->tenant_id === $tenantId
                && $membership->status === 'active';
        }));
    }
}

class TenantContextTest extends TestCase
{
    /** @var FakeTenantAccessRepository */
    private $repository;

    /** @var User */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            Settings::class,
            SetLocale::class,
            RoleManage::class,
        ]);

        $this->repository = new FakeTenantAccessRepository();
        $this->app->instance(TenantAccessRepository::class, $this->repository);

        $this->user = new User();
        $this->user->setAttribute('id', 1);
    }

    public function test_one_active_membership_automatically_establishes_tenant_one(): void
    {
        $tenant = $this->tenant(1, 'AYUGLOBAL');
        $this->repository->eligible = [$tenant];
        $this->allowMembership(1, 1, 'active', 9);
        $request = $this->requestFor($this->user);

        $result = app(TenantContext::class)->resolve($request, $this->user);

        $this->assertSame($tenant, $result['tenant']);
        $this->assertSame(['role_manage_id' => 9], $result['membership']);
        $this->assertFalse($result['selection_required']);
        $this->assertSame(1, $request->session()->get('tenant_context.id'));
        $this->assertSame(1, $request->session()->get('tenant_context.user_id'));
    }

    public function test_current_ayuglobal_user_resolves_tenant_one(): void
    {
        $tenant = $this->tenant(1, 'AYUGLOBAL');
        $this->repository->eligible = [$tenant];
        $this->allowMembership(1, 1);
        $request = $this->requestFor($this->user);

        $current = app(TenantContext::class)->currentTenant($request, $this->user);

        $this->assertSame(1, (int) $current->getKey());
        $this->assertSame('AYUGLOBAL', $current->name);
    }

    public function test_authenticated_request_user_resolves_tenant_without_user_override(): void
    {
        $tenant = $this->tenant(1, 'AYUGLOBAL');
        $this->repository->eligible = [$tenant];
        $this->allowMembership(1, 1);
        $request = $this->requestFor($this->user);

        $current = app(TenantContext::class)->currentTenant($request);

        $this->assertSame($tenant, $current);
        $this->assertSame(1, $request->session()->get('tenant_context.user_id'));
    }

    public function test_resolve_rejects_an_explicit_user_different_from_authenticated_user(): void
    {
        $otherUser = new User();
        $otherUser->setAttribute('id', 2);
        $otherTenant = $this->tenant(2, 'Other user organization');
        $this->repository->eligible = [$otherTenant];
        $request = $this->requestFor($this->user, [
            'tenant_context.id' => 2,
            'tenant_context.user_id' => 2,
        ]);

        $result = app(TenantContext::class)->resolve($request, $otherUser);

        $this->assertNull($result['tenant']);
        $this->assertFalse($result['selection_required']);
        $this->assertSame([], $result['eligible_tenants']->all());
        $this->assertFalse($request->session()->has('tenant_context.id'));
        $this->assertFalse($request->session()->has('tenant_context.user_id'));
    }

    public function test_set_tenant_cannot_store_another_users_tenant(): void
    {
        $otherUser = new User();
        $otherUser->setAttribute('id', 2);
        $otherTenant = $this->tenant(2, 'Other user organization');
        $this->repository->selectable = [2 => [2 => $otherTenant]];
        $request = $this->requestFor($this->user);

        $selected = app(TenantContext::class)->setTenant($request, $otherUser, 2);

        $this->assertNull($selected);
        $this->assertFalse($request->session()->has('tenant_context.id'));
        $this->assertFalse($request->session()->has('tenant_context.user_id'));
    }

    public function test_multiple_active_memberships_require_explicit_selection(): void
    {
        $this->repository->eligible = [
            $this->tenant(1, 'AYUGLOBAL'),
            $this->tenant(2, 'Second organization'),
        ];
        $this->allowMembership(1, 1);
        $this->allowMembership(1, 2);
        $request = $this->requestFor($this->user);

        $result = app(TenantContext::class)->resolve($request, $this->user);

        $this->assertNull($result['tenant']);
        $this->assertTrue($result['selection_required']);
        $this->assertFalse($request->session()->has('tenant_context.id'));
    }

    public function test_tenant_middleware_redirects_multi_tenant_user_to_selection(): void
    {
        $this->repository->eligible = [
            $this->tenant(1, 'AYUGLOBAL'),
            $this->tenant(2, 'Second organization'),
        ];
        $this->allowMembership(1, 1);
        $this->allowMembership(1, 2);
        $request = $this->requestFor($this->user);
        $middleware = new RequireTenantContext(app(TenantContext::class), app(Authenticate::class));

        $response = $middleware->handle($request, function () {
            return response('tenant reached');
        });

        $this->assertSame(route('tenant.select'), $response->headers->get('Location'));
        $this->assertFalse($request->session()->has('tenant_context.id'));
    }

    public function test_tenant_without_membership_cannot_be_selected(): void
    {
        $request = $this->requestFor($this->user);
        $this->repository->selectable = [];

        $selected = app(TenantContext::class)->setTenant($request, $this->user, 99);

        $this->assertNull($selected);
        $this->assertFalse($request->session()->has('tenant_context.id'));
    }

    public function test_suspended_tenant_cannot_be_selected(): void
    {
        $request = $this->requestFor($this->user);
        $suspended = $this->tenant(2, 'Suspended organization');
        $suspended->status = 'suspended';
        $this->repository->selectable = [1 => [2 => $suspended]];

        $this->assertNull(app(TenantContext::class)->setTenant($request, $this->user, 2));
    }

    public function test_suspended_membership_cannot_be_selected(): void
    {
        $request = $this->requestFor($this->user);
        $this->repository->selectable = [1 => [2 => $this->tenant(2, 'Suspended membership')]];
        $this->allowMembership(1, 2, 'suspended');

        $this->assertNull(app(TenantContext::class)->setTenant($request, $this->user, 2));
    }

    public function test_revoked_membership_cannot_be_selected(): void
    {
        $request = $this->requestFor($this->user);
        $this->repository->selectable = [1 => [2 => $this->tenant(2, 'Revoked membership')]];
        $this->allowMembership(1, 2, 'revoked');

        $this->assertNull(app(TenantContext::class)->setTenant($request, $this->user, 2));
    }

    public function test_missing_membership_does_not_resolve_tenant_or_discard_eligible_tenants(): void
    {
        $tenant = $this->tenant(1, 'AYUGLOBAL');
        $this->repository->eligible = [$tenant];
        $request = $this->requestFor($this->user);

        $result = app(TenantContext::class)->resolve($request, $this->user);

        $this->assertNull($result['tenant']);
        $this->assertNull($result['membership']);
        $this->assertSame([$tenant], $result['eligible_tenants']->all());
        $this->assertFalse($result['selection_required']);
    }

    public function test_revoked_membership_does_not_resolve_tenant(): void
    {
        $tenant = $this->tenant(1, 'AYUGLOBAL');
        $this->repository->eligible = [$tenant];
        $this->allowMembership(1, 1, 'revoked');
        $request = $this->requestFor($this->user);

        $result = app(TenantContext::class)->resolve($request, $this->user);

        $this->assertNull($result['tenant']);
        $this->assertNull($result['membership']);
        $this->assertSame([$tenant], $result['eligible_tenants']->all());
    }

    public function test_duplicate_active_memberships_fail_closed(): void
    {
        $tenant = $this->tenant(1, 'AYUGLOBAL');
        $this->repository->eligible = [$tenant];
        $this->allowMembership(1, 1);
        $this->allowMembership(1, 1);
        $request = $this->requestFor($this->user);

        $result = app(TenantContext::class)->resolve($request, $this->user);

        $this->assertNull($result['tenant']);
        $this->assertNull($result['membership']);
        $this->assertSame([$tenant], $result['eligible_tenants']->all());
    }

    public function test_membership_for_different_user_or_tenant_is_not_returned(): void
    {
        $tenant = $this->tenant(1, 'AYUGLOBAL');
        $this->allowMembership(1, 2);
        $this->allowMembership(2, 1);

        $this->assertNull($this->repository->findEligibleMembership($this->user, $tenant));
    }

    public function test_zero_eligible_memberships_clear_context_and_deny_access(): void
    {
        $this->repository->eligible = [];
        $request = $this->requestFor($this->user, [
            'tenant_context.id' => 1,
            'tenant_context.user_id' => 1,
        ]);
        $middleware = new RequireTenantContext(app(TenantContext::class), app(Authenticate::class));

        $response = $middleware->handle($request, function () {
            return response('tenant reached');
        });

        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($request->session()->has('tenant_context.id'));
        $this->assertStringContainsString(
            'No active organization access is available for your account.',
            $response->getContent()
        );
    }

    public function test_invalid_session_tenant_is_cleared_for_multi_tenant_user(): void
    {
        $this->repository->eligible = [
            $this->tenant(1, 'AYUGLOBAL'),
            $this->tenant(2, 'Second organization'),
        ];
        $this->allowMembership(1, 1);
        $this->allowMembership(1, 2);
        $request = $this->requestFor($this->user, [
            'tenant_context.id' => 999,
            'tenant_context.user_id' => 1,
        ]);

        $result = app(TenantContext::class)->resolve($request, $this->user);

        $this->assertTrue($result['selection_required']);
        $this->assertNull($result['tenant']);
        $this->assertFalse($request->session()->has('tenant_context.id'));
        $this->assertFalse($request->session()->has('tenant_context.user_id'));
    }

    public function test_session_tenant_for_another_user_is_rejected(): void
    {
        $this->repository->eligible = [
            $this->tenant(1, 'AYUGLOBAL'),
            $this->tenant(2, 'Second organization'),
        ];
        $this->allowMembership(1, 1);
        $this->allowMembership(1, 2);
        $request = $this->requestFor($this->user, [
            'tenant_context.id' => 2,
            'tenant_context.user_id' => 77,
        ]);

        $result = app(TenantContext::class)->resolve($request, $this->user);

        $this->assertTrue($result['selection_required']);
        $this->assertNull($result['tenant']);
        $this->assertFalse($request->session()->has('tenant_context.id'));
    }

    public function test_valid_tenant_selection_establishes_context(): void
    {
        $tenant = $this->tenant(1, 'AYUGLOBAL');
        $this->repository->selectable = [1 => [1 => $tenant]];
        $this->allowMembership(1, 1);

        $response = $this->actingAs($this->user)
            ->post(route('tenant.select.store'), ['tenant_id' => '1']);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('tenant_context.id', 1);
        $response->assertSessionHas('tenant_context.user_id', 1);
    }

    public function test_invalid_post_does_not_replace_valid_current_selection(): void
    {
        $existing = $this->tenant(1, 'AYUGLOBAL');
        $this->repository->selectable = [1 => [1 => $existing]];

        $response = $this->from(route('tenant.select'))
            ->actingAs($this->user)
            ->withSession([
                'tenant_context.id' => 1,
                'tenant_context.user_id' => 1,
            ])
            ->post(route('tenant.select.store'), ['tenant_id' => '999']);

        $response->assertRedirect(route('tenant.select'));
        $response->assertSessionHas('tenant_context.id', 1);
        $response->assertSessionHas('tenant_context.user_id', 1);
        $response->assertSessionHasErrors('tenant_id');
    }

    public function test_unauthenticated_user_cannot_use_selection_routes(): void
    {
        $this->get(route('tenant.select'))->assertRedirect(route('login'));
        $this->post(route('tenant.select.store'), ['tenant_id' => 1])->assertRedirect(route('login'));
    }

    public function test_selection_route_is_reachable_without_existing_context(): void
    {
        $this->repository->eligible = [
            $this->tenant(1, 'AYUGLOBAL'),
            $this->tenant(2, 'Second organization'),
        ];

        $route = app('router')->getRoutes()->getByName('tenant.select');
        $this->assertNotContains('tenant', $route->gatherMiddleware());

        $response = $this->actingAs($this->user)->get(route('tenant.select'));

        $response->assertOk();
        $response->assertSee('Select your organization');
        $response->assertSee('AYUGLOBAL');
        $response->assertSee('Second organization');
    }

    public function test_tenant_middleware_auto_selects_the_single_eligible_tenant(): void
    {
        $tenant = $this->tenant(1, 'AYUGLOBAL');
        $this->repository->eligible = [$tenant];
        $this->allowMembership(1, 1);
        $request = $this->requestFor($this->user);
        $middleware = new RequireTenantContext(app(TenantContext::class), app(Authenticate::class));

        $response = $middleware->handle($request, function () {
            return response('tenant reached');
        });

        $this->assertSame('tenant reached', $response->getContent());
        $this->assertSame(1, $request->session()->get('tenant_context.id'));
    }

    public function test_previously_selected_tenant_is_cleared_when_membership_becomes_revoked(): void
    {
        $tenant = $this->tenant(1, 'AYUGLOBAL');
        $this->repository->eligible = [$tenant];
        $this->allowMembership(1, 1);
        $request = $this->requestFor($this->user);
        $context = app(TenantContext::class);

        $this->assertSame($tenant, $context->resolve($request, $this->user)['tenant']);
        $this->repository->memberships[1][1][0]->status = 'revoked';
        $this->repository->eligible = [];

        $result = $context->resolve($request, $this->user);

        $this->assertNull($result['tenant']);
        $this->assertNull($result['membership']);
        $this->assertSame([], $result['eligible_tenants']->all());
        $this->assertFalse($result['selection_required']);
        $this->assertFalse($request->session()->has('tenant_context.id'));
        $this->assertFalse($request->session()->has('tenant_context.user_id'));
    }

    public function test_tenant_middleware_clears_guest_context_before_authentication_redirect(): void
    {
        $request = $this->requestFor($this->user, [
            'tenant_context.id' => 1,
            'tenant_context.user_id' => 1,
        ]);
        $request->setUserResolver(function () {
            return null;
        });
        $middleware = new RequireTenantContext(app(TenantContext::class), app(Authenticate::class));

        try {
            $middleware->handle($request, function () {
                return response('tenant reached');
            });
            $this->fail('Unauthenticated tenant access should invoke the existing authentication middleware.');
        } catch (\Illuminate\Auth\AuthenticationException $exception) {
            $this->assertSame('Unauthenticated.', $exception->getMessage());
        }

        $this->assertFalse($request->session()->has('tenant_context.id'));
        $this->assertFalse($request->session()->has('tenant_context.user_id'));
    }

    public function test_eligibility_query_requires_active_tenant_and_active_user_membership(): void
    {
        $query = (new TenantAccessRepository())->eligibleTenantQuery($this->user);
        $sql = strtolower($query->toSql());

        $this->assertStringContainsString('tenants', $sql);
        $this->assertStringContainsString('tenant_memberships', $sql);
        $this->assertSame(['active', 1, 'active'], $query->getBindings());
    }

    public function test_session_invalidation_clears_tenant_context(): void
    {
        $session = $this->newSession([
            'tenant_context.id' => 1,
            'tenant_context.user_id' => 1,
        ]);

        $session->invalidate();

        $this->assertNull($session->get('tenant_context.id'));
        $this->assertNull($session->get('tenant_context.user_id'));
    }

    public function test_existing_logout_endpoint_invalidates_tenant_context(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession([
                'tenant_context.id' => 1,
                'tenant_context.user_id' => 1,
            ])
            ->post(route('logout'));

        $response->assertRedirect();
        $response->assertSessionMissing('tenant_context.id');
        $response->assertSessionMissing('tenant_context.user_id');
    }

    private function tenant(int $id, string $name): Tenant
    {
        $tenant = new Tenant();
        $tenant->setRawAttributes([
            'id' => $id,
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)),
            'status' => 'active',
        ]);

        return $tenant;
    }

    private function allowMembership(int $userId, int $tenantId, string $status = 'active', ?int $roleManageId = null): void
    {
        $membership = new TenantMembership();
        $membership->setRawAttributes([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'status' => $status,
            'role_manage_id' => $roleManageId,
        ]);

        $this->repository->memberships[$userId][$tenantId][] = $membership;
    }

    private function requestFor(User $user, array $sessionValues = []): Request
    {
        $request = Request::create('/tenant/test');
        $request->setLaravelSession($this->newSession($sessionValues));
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $request;
    }

    private function newSession(array $values = []): Store
    {
        $session = new Store('tenant-context-test', new ArraySessionHandler(120));
        $session->start();
        foreach ($values as $key => $value) {
            $session->put($key, $value);
        }

        return $session;
    }
}
