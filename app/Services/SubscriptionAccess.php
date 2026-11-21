<?php

namespace App\Services;

use App\BusinessSetting;
use Carbon\Carbon;

class SubscriptionAccess
{
    public function hasActiveSubscription(?BusinessSetting $business): bool
    {
        return $business
            && strtolower((string) $business->subscription_status) === 'active'
            && !$this->isExpired($business);
    }

    public function isExpired(?BusinessSetting $business): bool
    {
        $attributes = $business ? $business->getAttributes() : [];
        $expiration = $attributes['subscription_expires_at'] ?? null;
        if (!$expiration) {
            return false;
        }

        return Carbon::today()->greaterThanOrEqualTo(
            Carbon::parse($expiration)->startOfDay()
        );
    }

    public function isPaymentPending(?BusinessSetting $business): bool
    {
        return $business
            && in_array(strtolower((string) $business->subscription_status), [
                'pending_payment',
                'verification_pending',
            ], true);
    }
}
