<?php

namespace App\Services;

use App\Tenant;
use App\TenantMembership;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TenantAccessRepository
{
    public function eligibleTenants(User $user): Collection
    {
        return $this->eligibleTenantQuery($user)
            ->orderBy('tenants.name')
            ->get();
    }

    public function findEligibleTenant(User $user, $tenantId): ?Tenant
    {
        if (!is_int($tenantId) && !(is_string($tenantId) && ctype_digit($tenantId))) {
            return null;
        }

        $tenantId = (int) $tenantId;
        if ($tenantId < 1) {
            return null;
        }

        return $this->eligibleTenantQuery($user)
            ->whereKey($tenantId)
            ->first();
    }

    public function findEligibleMembership(User $user, Tenant $tenant): ?TenantMembership
    {
        $matches = TenantMembership::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('user_id', $user->getAuthIdentifier())
            ->where('status', 'active')
            ->whereHas('tenant', function ($query) {
                $query->where('status', 'active');
            })
            ->limit(2)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * Keep the membership and tenant status conditions in one query definition
     * so selection and request validation use the same authorization rule.
     */
    public function eligibleTenantQuery(User $user): Builder
    {
        return Tenant::query()
            ->where('tenants.status', 'active')
            ->whereHas('memberships', function ($query) use ($user) {
                $query->where('tenant_memberships.user_id', $user->getAuthIdentifier())
                    ->where('tenant_memberships.status', 'active');
            });
    }
}
