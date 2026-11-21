<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;

class RequireTenantContext
{
    /** @var TenantContext */
    private $tenantContext;

    /** @var Authenticate */
    private $authenticate;

    public function __construct(TenantContext $tenantContext, Authenticate $authenticate)
    {
        $this->tenantContext = $tenantContext;
        $this->authenticate = $authenticate;
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            $this->tenantContext->clear($request);

            return $this->authenticate->handle($request, $next);
        }

        $resolution = $this->tenantContext->resolve($request, $user);

        if (!$resolution['tenant'] && $user->business && $user->business->subscription_status !== 'active') {
            return redirect()->route('business.payment');
        }

        if ($resolution['tenant']) {
            return $next($request);
        }

        if ($resolution['selection_required']) {
            return redirect()->route('tenant.select');
        }

        return response()->view('tenant.unavailable', [], 403);
    }
}
