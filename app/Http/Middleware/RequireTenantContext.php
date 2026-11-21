<?php

namespace App\Http\Middleware;

use App\BusinessSetting;
use App\Services\SubscriptionAccess;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;

class RequireTenantContext
{
    /** @var TenantContext */
    private $tenantContext;

    /** @var Authenticate */
    private $authenticate;

    /** @var SubscriptionAccess */
    private $subscriptions;

    public function __construct(
        TenantContext $tenantContext,
        Authenticate $authenticate,
        SubscriptionAccess $subscriptions
    )
    {
        $this->tenantContext = $tenantContext;
        $this->authenticate = $authenticate;
        $this->subscriptions = $subscriptions;
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            $this->tenantContext->clear($request);

            return $this->authenticate->handle($request, $next);
        }

        $resolution = $this->tenantContext->resolve($request, $user);
        $tenant = $resolution['tenant'];
        $business = $tenant
            ? BusinessSetting::where('tenant_id', $tenant->getKey())->first()
            : $user->business;

        if (($tenant || $user->business) && !$this->subscriptions->hasActiveSubscription($business)) {
            if ($this->subscriptions->isPaymentPending($business)) {
                return redirect()->route('business.payment');
            }

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your subscription is inactive or expired. Use the renewal link sent to your business email to restore access.',
            ]);
        }

        if ($tenant) {
            return $next($request);
        }

        if ($resolution['selection_required']) {
            return redirect()->route('tenant.select');
        }

        return response()->view('tenant.unavailable', [], 403);
    }
}
