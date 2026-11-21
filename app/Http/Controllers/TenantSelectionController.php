<?php

namespace App\Http\Controllers;

use App\Services\TenantContext;
use App\User;
use Illuminate\Http\Request;

class TenantSelectionController extends Controller
{
    public function show(Request $request, TenantContext $tenantContext)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $tenants = $tenantContext->eligibleTenants($request, $user);
        if ($tenants->isEmpty()) {
            return response()->view('tenant.unavailable', [], 403);
        }

        return view('tenant.select', compact('tenants'));
    }

    public function select(Request $request, TenantContext $tenantContext)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'min:1'],
        ]);

        if (!$tenantContext->setTenant($request, $user, $validated['tenant_id'])) {
            return redirect()
                ->route('tenant.select')
                ->withErrors(['tenant_id' => 'Please select an available organization.']);
        }

        return redirect()->route('dashboard');
    }
}
