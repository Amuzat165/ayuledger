<?php

namespace Tests\Unit;

use App\BusinessSetting;
use App\Services\TenantResourceLimits;
use App\SubscriptionPlan;

class TenantResourceLimitsTest extends \PHPUnit\Framework\TestCase
{
    public function test_plan_limits_are_used_when_business_overrides_are_blank(): void
    {
        $business = new BusinessSetting(['subscription_plan' => 'basic']);
        $plan = new SubscriptionPlan(['max_users' => 5, 'max_branches' => 2]);

        $this->assertSame(
            ['users' => 5, 'branches' => 2],
            (new TenantResourceLimits())->resolve($business, $plan)
        );
    }

    public function test_business_overrides_replace_plan_limits_and_zero_is_enforced(): void
    {
        $business = new BusinessSetting([
            'subscription_plan' => 'basic',
            'max_users_override' => 8,
            'max_branches_override' => 0,
        ]);
        $plan = new SubscriptionPlan(['max_users' => 5, 'max_branches' => 2]);

        $this->assertSame(
            ['users' => 8, 'branches' => 0],
            (new TenantResourceLimits())->resolve($business, $plan)
        );
    }

    public function test_unlimited_plan_branches_remain_unlimited_without_an_override(): void
    {
        $business = new BusinessSetting(['subscription_plan' => 'premium']);
        $plan = new SubscriptionPlan(['max_users' => 50, 'max_branches' => null]);

        $this->assertSame(
            ['users' => 50, 'branches' => null],
            (new TenantResourceLimits())->resolve($business, $plan)
        );
    }

    public function test_branch_plan_defaults_match_the_configured_quotas(): void
    {
        $migration = file_get_contents(
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
                . 'database' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR
                . '2026_11_21_000002_add_subscription_resource_limits.php'
        );

        $this->assertStringContainsString("where('slug', 'basic')->update(['max_branches' => 2])", $migration);
        $this->assertStringContainsString("where('slug', 'standard')->update(['max_branches' => 5])", $migration);
        $this->assertStringContainsString("where('slug', 'premium')->update(['max_branches' => null])", $migration);
    }
}
