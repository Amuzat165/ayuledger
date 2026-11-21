<?php

namespace App\Services;

use App\BusinessSetting;
use App\SubscriptionPlan;

class TenantResourceLimits
{
    public function forTenant(int $tenantId): array
    {
        return $this->forBusiness(
            BusinessSetting::where('tenant_id', $tenantId)->first()
        );
    }

    public function forBusiness(?BusinessSetting $business): array
    {
        if (!$business) {
            return ['users' => 0, 'branches' => 0];
        }

        $plan = SubscriptionPlan::where('slug', $business->subscription_plan)->first();

        return $this->resolve($business, $plan);
    }

    public function resolve(?BusinessSetting $business, ?SubscriptionPlan $plan): array
    {
        if (!$business) {
            return ['users' => 0, 'branches' => 0];
        }

        $userLimit = $business->max_users_override;
        $branchLimit = $business->max_branches_override;

        if ($plan) {
            if ($userLimit === null) {
                $userLimit = (int) $plan->max_users;
            }
            if ($branchLimit === null && $plan->max_branches !== null) {
                $branchLimit = (int) $plan->max_branches;
            }
        }

        return [
            'users' => $userLimit === null ? null : (int) $userLimit,
            'branches' => $branchLimit === null ? null : (int) $branchLimit,
        ];
    }
}
