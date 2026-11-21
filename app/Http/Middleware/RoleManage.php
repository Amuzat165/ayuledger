<?php

namespace App\Http\Middleware;

use Closure;
use App\RoleManage as RoleManageModel;
use App\Services\TenantContext;
use App\User;
use Illuminate\Http\Request;

class RoleManage
{
    /** @var TenantContext */
    private $tenantContext;

    private const PERMISSION_INDEXES = [
        'All' => 1,
        'Show' => 2,
        'Create' => 3,
        'Edit' => 4,
        'Delete' => 5,
        'Pdf' => 6,
        'TrashShow' => 7,
        'Restore' => 8,
        'PermanentlyDelete' => 9,
    ];

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Resolve role permissions without inheriting permissive config defaults.
     * RoleManageController stores permission flags as integer 0 or 1.
     */
    public static function permissionsFromRoleContents(array $roleContents, array $modules): array
    {
        $resolved = [];

        foreach ($modules as $module => $defaults) {
            $row = isset($roleContents[$module]) && is_array($roleContents[$module])
                ? $roleContents[$module]
                : [];

            foreach (self::PERMISSION_INDEXES as $permission => $index) {
                $resolved['role_manage.' . $module . '.' . $permission] =
                    isset($row[$index]) && $row[$index] === 1 ? 1 : 0;
            }
        }

        return $resolved;
    }

    protected function findRole($roleId): ?RoleManageModel
    {
        return $roleId ? RoleManageModel::withoutGlobalScopes()->find($roleId) : null;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (auth()->check() == false) {
            return $next($request);
        }

        $configRoleManages = config('role_manage', []);
        $user = auth()->user();
        $roleId = $user->role_manage_id;

        if ($this->isTenantScopedRequest($request)) {
            $resolution = $user instanceof User
                ? $this->tenantContext->resolve($request, $user)
                : [];
            $tenant = $resolution['tenant'] ?? null;
            $membership = $resolution['membership'] ?? null;

            // Tenant-scoped permissions must come from the resolved active
            // membership. Missing context or role data never falls back to
            // the user's legacy global role.
            $roleId = $tenant && is_array($membership)
                ? $this->validTenantRoleId($membership['role_manage_id'] ?? null)
                : null;
        }

        $role = $this->findRole($roleId);
        $decoded = $role ? json_decode($role->content, true) : null;
        $roleContents = is_array($decoded) ? $decoded : [];

        config(self::permissionsFromRoleContents($roleContents, $configRoleManages));

        if ($role && strcasecmp($role->name, 'Admin') === 0) {
            $adminPermissions = [];
            foreach ($configRoleManages as $module => $defaults) {
                foreach (array_keys(self::PERMISSION_INDEXES) as $permission) {
                    $adminPermissions['role_manage.' . $module . '.' . $permission] = 1;
                }
            }
            config($adminPermissions);
        }

        return $next($request);
    }

    private function isTenantScopedRequest(Request $request): bool
    {
        $route = $request->route();
        if (!$route || !method_exists($route, 'gatherMiddleware')) {
            return false;
        }

        return in_array('tenant', $route->gatherMiddleware(), true)
            || in_array(\App\Http\Middleware\RequireTenantContext::class, $route->gatherMiddleware(), true);
    }

    private function validTenantRoleId($roleId): ?int
    {
        if (!is_int($roleId) && !(is_string($roleId) && ctype_digit($roleId))) {
            return null;
        }

        $roleId = (int) $roleId;

        return $roleId > 0 ? $roleId : null;
    }
}
