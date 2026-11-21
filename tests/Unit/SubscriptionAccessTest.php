<?php

namespace Tests\Unit;

use App\BusinessSetting;
use App\Services\SubscriptionAccess;
use Carbon\Carbon;

class SubscriptionAccessTest extends \PHPUnit\Framework\TestCase
{
    public function test_active_subscription_is_allowed_until_its_expiration_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 10:00:00'));
        try {
            $business = $this->business([
                'subscription_status' => 'active',
                'subscription_expires_at' => '2026-11-22',
            ]);

            $this->assertTrue((new SubscriptionAccess())->hasActiveSubscription($business));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_active_subscription_is_denied_on_its_expiration_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-21 10:00:00'));
        try {
            $business = $this->business([
                'subscription_status' => 'active',
                'subscription_expires_at' => '2026-11-21',
            ]);

            $this->assertFalse((new SubscriptionAccess())->hasActiveSubscription($business));
            $this->assertTrue((new SubscriptionAccess())->isExpired($business));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_non_active_subscription_status_is_denied(): void
    {
        $business = $this->business([
            'subscription_status' => 'verification_pending',
            'subscription_expires_at' => null,
        ]);

        $this->assertFalse((new SubscriptionAccess())->hasActiveSubscription($business));
    }

    private function business(array $attributes): BusinessSetting
    {
        $business = new BusinessSetting();
        $business->setRawAttributes($attributes, true);

        return $business;
    }
}
