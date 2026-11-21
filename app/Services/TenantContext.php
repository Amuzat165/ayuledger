<?php

namespace App\Services;

use App\Tenant;
use App\TenantMembership;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TenantContext
{
    private const TENANT_SESSION_KEY = 'tenant_context.id';
    private const USER_SESSION_KEY = 'tenant_context.user_id';

    /** @var TenantAccessRepository */
    private $tenants;

    public function __construct(TenantAccessRepository $tenants)
    {
        $this->tenants = $tenants;
    }

    public function eligibleTenants(Request $request, ?User $user = null): Collection
    {
        $user = $this->authenticatedUser($request, $user);

        return $user ? $this->tenants->eligibleTenants($user) : collect();
    }

    private function validateTenantForUser(User $user, $tenantId): ?Tenant
    {
        $normalizedId = $this->normalizeId($tenantId);

        return $normalizedId === null
            ? null
            : $this->tenants->findEligibleTenant($user, $normalizedId);
    }

    /**
     * Store a selection only after the database confirms active membership
     * and active tenant status for this authenticated user.
     */
    public function setTenant(Request $request, User $user, $tenantId): ?Tenant
    {
        $authenticatedUser = $this->authenticatedUser($request, $user);
        if (!$authenticatedUser) {
            return null;
        }

        $tenant = $this->validateTenantForUser($authenticatedUser, $tenantId);
        if (!$tenant) {
            return null;
        }

        $this->storeSelection($request, $authenticatedUser, $tenant);

        return $tenant;
    }

    public function clear(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->forget([
                self::TENANT_SESSION_KEY,
                self::USER_SESSION_KEY,
            ]);
        }
    }

    /**
     * Resolve the session hint against the current database-authorized list.
     * The returned tenant is always from that freshly queried list.
     */
    public function resolve(Request $request, ?User $user = null): array
    {
        $user = $this->authenticatedUser($request, $user);
        if (!$user) {
            $this->clear($request);

            return $this->resolution(collect(), null, false);
        }

        $eligible = $this->tenants->eligibleTenants($user);
        if ($eligible->isEmpty()) {
            $this->clear($request);

            return $this->resolution($eligible, null, false);
        }

        if ($eligible->count() === 1) {
            $tenant = $eligible->first();
            $membership = $this->tenants->findEligibleMembership($user, $tenant);
            if (!$membership) {
                $this->clear($request);

                return $this->resolution($eligible, null, false);
            }

            $this->storeSelection($request, $user, $tenant);

            return $this->resolution(
                $eligible,
                $tenant,
                false,
                $this->membershipContext($membership)
            );
        }

        $sessionUserId = $request->hasSession()
            ? $request->session()->get(self::USER_SESSION_KEY)
            : null;
        $sessionTenantId = $request->hasSession()
            ? $this->normalizeId($request->session()->get(self::TENANT_SESSION_KEY))
            : null;

        if ((string) $sessionUserId === (string) $user->getAuthIdentifier() && $sessionTenantId !== null) {
            $selected = $eligible->first(function (Tenant $tenant) use ($sessionTenantId) {
                return (int) $tenant->getKey() === $sessionTenantId;
            });

            if ($selected) {
                $membership = $this->tenants->findEligibleMembership($user, $selected);
                if (!$membership) {
                    $this->clear($request);

                    return $this->resolution($eligible, null, true);
                }

                return $this->resolution(
                    $eligible,
                    $selected,
                    false,
                    $this->membershipContext($membership)
                );
            }
        }

        $this->clear($request);

        return $this->resolution($eligible, null, true);
    }

    public function currentTenant(Request $request, ?User $user = null): ?Tenant
    {
        return $this->resolve($request, $user)['tenant'];
    }

    public function hasCurrentTenant(Request $request, ?User $user = null): bool
    {
        return $this->currentTenant($request, $user) !== null;
    }

    /**
     * An optional user argument is accepted for callers that already resolved
     * the request user, but it can never override the authenticated identity.
     */
    private function authenticatedUser(Request $request, ?User $candidate): ?User
    {
        $authenticatedUser = $request->user();
        if (!$authenticatedUser instanceof User) {
            return null;
        }

        if ($candidate && (string) $candidate->getAuthIdentifier() !== (string) $authenticatedUser->getAuthIdentifier()) {
            return null;
        }

        return $authenticatedUser;
    }

    private function storeSelection(Request $request, User $user, Tenant $tenant): void
    {
        if (!$request->hasSession()) {
            return;
        }

        $request->session()->put(self::TENANT_SESSION_KEY, (int) $tenant->getKey());
        $request->session()->put(self::USER_SESSION_KEY, (int) $user->getAuthIdentifier());
    }

    private function normalizeId($id): ?int
    {
        if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
            return null;
        }

        $id = (int) $id;

        return $id > 0 ? $id : null;
    }

    private function membershipContext(TenantMembership $membership): array
    {
        return [
            'role_manage_id' => $membership->role_manage_id === null
                ? null
                : (int) $membership->role_manage_id,
        ];
    }

    private function resolution(
        Collection $eligible,
        ?Tenant $tenant,
        bool $selectionRequired,
        ?array $membership = null
    ): array
    {
        return [
            'eligible_tenants' => $eligible,
            'tenant' => $tenant,
            'selection_required' => $selectionRequired,
            'has_eligible_tenants' => $eligible->isNotEmpty(),
            'membership' => $membership,
        ];
    }
}
