<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;

/**
 * Keep legacy, globally queried accounting workflows restricted to Tenant 1
 * until their data access is converted to tenant-aware queries.
 */
class RequireLegacyTenantOne
{
    /** @var TenantContext */
    private $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    public function handle(Request $request, Closure $next)
    {
        $tenant = $this->tenantContext->currentTenant($request);

        abort_unless($tenant && (int) $tenant->getKey() === 1, 403);

        return $next($request);
    }
}
