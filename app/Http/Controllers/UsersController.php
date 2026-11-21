<?php

namespace App\Http\Controllers;

use App\User;
use App\Profile;
use App\TenantMembership;
use App\Services\TenantResourceLimits;
use App\BusinessSetting;
use App\RoleManage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class UsersController extends Controller
{
    //    Important properties
    public $parentModel = User::class;
    public $parentRoute = 'user';
    public $parentView = "admin.user";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tenantId = $this->currentTenantId();
        if (!$tenantId) abort(403);
        $itemsQuery = $this->parentModel::where('business_id', $tenantId)->with('role')->orderBy('created_at', 'desc');
        if (env('DEMO_MODE') == true) {
            $itemsQuery->where('id', '!=', 1);
        } else {
        }
        $items = $itemsQuery->paginate(60);
        return view($this->parentView . '.index')->with('items', $items);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->ensureTenantAdmin();
        $tenantId = $this->currentTenantId();
        abort_unless($tenantId, 403);

        $currentCount = $this->tenantUserCount($tenantId);
        $limit = app(TenantResourceLimits::class)->forTenant($tenantId)['users'];

        return view($this->parentView . '.create')->with([
            'tenantRoles' => RoleManage::withoutGlobalScopes()->orderBy('created_at', 'desc')
                ->where('name', '!=', 'Super Admin')->get(),
            'tenantUserCount' => $currentCount,
            'tenantUserLimit' => $limit,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->ensureTenantAdmin();
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:1|confirmed',
            'role_manage_id' => 'required|min:1'
        ]);
        $tenantId = $this->currentTenantId();
        if (!$tenantId) abort(403);

        $role = RoleManage::withoutGlobalScopes()->whereKey($request->role_manage_id)
            ->where('name', '!=', 'Super Admin')->first();
        if (!$role) {
            return back()->withInput()->withErrors(['role_manage_id' => 'The selected role is not available to tenants.']);
        }

        $limits = app(TenantResourceLimits::class);
        $userLimit = $limits->forTenant($tenantId)['users'];
        if ($userLimit !== null && $this->tenantUserCount($tenantId) >= $userLimit) {
            return back()->withInput()->withErrors(['users' => 'Your subscription user limit has been reached.']);
        }

        \DB::transaction(function () use ($request, $tenantId, $limits) {
            $business = BusinessSetting::where('tenant_id', $tenantId)->lockForUpdate()->first();
            $userLimit = $limits->forBusiness($business)['users'];
            if ($userLimit !== null && $this->tenantUserCount($tenantId) >= $userLimit) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'users' => 'Your subscription user limit has been reached.',
                ]);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role_manage_id' => $request->role_manage_id,
                'business_id' => $tenantId,
            ]);

            Profile::create(['user_id' => $user->id]);
            TenantMembership::create([
                'tenant_id' => $tenantId,
                'business_id' => $tenantId,
                'user_id' => $user->id,
                'role_manage_id' => $user->role_manage_id,
                'status' => 'active',
            ]);
        });

        Session::flash('success', __('ui.messages.created'));
        return redirect()->back();
    }

    private function currentTenantId()
    {
        return Auth::user()->business_id ?: optional(Auth::user()->tenantMemberships()->where('status', 'active')->first())->tenant_id;
    }

    private function ensureTenantAdmin()
    {
        $role = Auth::user()->role;
        abort_unless($role && strcasecmp($role->name, 'Admin') === 0, 403, 'Only the tenant administrator can create users.');
    }

    private function tenantUserCount($tenantId)
    {
        return \DB::table('tenant_memberships')
            ->join('users', 'users.id', '=', 'tenant_memberships.user_id')
            ->where('tenant_memberships.tenant_id', $tenantId)
            ->where('tenant_memberships.status', 'active')
            ->whereNull('users.deleted_at')
            ->count();
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $tenantId = $this->currentTenantId();
        $item = $this->parentModel::withTrashed()
            ->where('business_id', $tenantId)
            ->find($request->id);
        if (empty($item)) {
            Session::flash('error', __('ui.messages.not_found'));
            return redirect()->route('role-manage');
        }
        $content = json_decode($item['content']);
        $item['content'] = $content;
        return view($this->parentView . '.show')->with('items', $item);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $this->ensureTenantAdmin();
        $tenantId = $this->currentTenantId();
        $items = $this->parentModel::where('business_id', $tenantId)->findOrFail($id);
        return view($this->parentView . '.edit')->with([
            'item' => $items,
            'tenantRoles' => RoleManage::orderBy('created_at', 'desc')
                ->where('name', '!=', 'Super Admin')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'role_manage_id' => 'numeric|min:1'
        ]);

        $this->ensureTenantAdmin();
        $tenantId = $this->currentTenantId();
        $user = User::where('business_id', $tenantId)->findOrFail($id);
        abort_unless(RoleManage::whereKey($request->role_manage_id)
            ->where('name', '!=', 'Super Admin')->exists(), 403);
        if ($request->password === $request->confirm_password and  !empty($request->password)) {

            $user->password = bcrypt($request->password);
        } elseif ($request->password !== $request->confirm_password) {

            Session::flash('error', __('ui.messages.password_confirmation_mismatch'));
            return redirect()->back();
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role_manage_id = $request->role_manage_id;


        $user->save();
        TenantMembership::where('user_id', $user->id)
            ->where('tenant_id', $tenantId)
            ->update(['role_manage_id' => $user->role_manage_id]);
        Session::flash('success', __('ui.messages.updated'));
        return redirect()->route($this->parentRoute);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (Auth::id() == $id) {
            Session::flash('error', __('ui.messages.cannot_delete_self'));
            return redirect()->back();
        }
        $tenantId = $this->currentTenantId();
        $user = User::where('business_id', $tenantId)->findOrFail($id);
        TenantMembership::where('user_id', $user->id)->where('tenant_id', $tenantId)->delete();
        $user->delete();
        Session::flash('success', __('ui.messages.trashed'));
        return redirect()->back();
    }


    public function trashed()
    {
        $tenantId = $this->currentTenantId();
        $items = $this->parentModel::onlyTrashed()->where('business_id', $tenantId)->with('role')->paginate(60);
        return view($this->parentView . '.trashed')->with("items", $items);
    }

    public function trashedShow()
    {
        $id = $_POST["id"];
        $tenantId = $this->currentTenantId();
        $trashedItem = $this->parentModel::onlyTrashed()
            ->where('business_id', $tenantId)->where('id', $id)->first();
        return response()->json($trashedItem);
    }

    public function restore($id)
    {
        $tenantId = $this->currentTenantId();
        $project = $this->parentModel::onlyTrashed()
            ->where('business_id', $tenantId)->where('id', $id)->firstOrFail();

        $project->restore();
        $membership = TenantMembership::where('tenant_id', $tenantId)
            ->where('user_id', $project->id)->first();
        if (!$membership) {
            TenantMembership::create([
                'tenant_id' => $tenantId,
                'business_id' => $tenantId,
                'user_id' => $project->id,
                'role_manage_id' => $project->role_manage_id,
                'status' => 'active',
            ]);
        }
        Session::flash('success', __('ui.messages.restored'));
        return redirect()->back();
    }

    public function kill($id)
    {
        $tenantId = $this->currentTenantId();
        $project = $this->parentModel::onlyTrashed()
            ->where('business_id', $tenantId)->where('id', $id)->firstOrFail();
        $project->forceDelete();

        //        For Profile
        $profile = Profile::where('user_id', $id)->first();
        if ($profile) $profile->delete();

        Session::flash('success', __('ui.messages.permanently_deleted'));
        return redirect()->back();
    }

    public function activeSearch(Request $request)
    {

        $request->validate([
            'search' => 'min:1'
        ]);

        $search = $request["search"];
        $items = $this->parentModel::where('name', 'like', '%' . $search . '%')
            ->where('business_id', $this->currentTenantId())
            ->with('role')->paginate(60);

        return view($this->parentView . '.index')
            ->with('items', $items);
    }

    public function trashedSearch(Request $request)
    {

        $request->validate([
            'search' => 'min:1'
        ]);


        $search = $request["search"];
        $items = $this->parentModel::where('name', 'like', '%' . $search . '%')
            ->onlyTrashed()
            ->where('business_id', $this->currentTenantId())
            ->with('role')->paginate(60);

        return view($this->parentView . '.trashed')
            ->with('items', $items);
    }


    //    Fixed Method for all
    public function activeAction(Request $request)
    {

        $request->validate([
            'items' => 'required'
        ]);

        if ($request->apply_comand_top == 3 || $request->apply_comand_bottom == 3) {
            foreach ($request->items["id"] as $id) {
                $this->destroy($id);
            }

            return redirect()->back();
        } else {
            Session::flash('error', __('ui.messages.operation_failed'));
            return redirect()->back();
        }
    }

    public function trashedAction(Request $request)
    {

        $request->validate([
            'items' => 'required'
        ]);

        if ($request->apply_comand_top == 1 || $request->apply_comand_bottom == 1) {

            foreach ($request->items["id"] as $id) {
                $this->restore($id);
            }
        } elseif ($request->apply_comand_top == 2 || $request->apply_comand_bottom == 2) {

            foreach ($request->items["id"] as $id) {

                $this->kill($id);
            }
            return redirect()->back();
        } else {
            Session::flash('error', __('ui.messages.operation_failed'));
            return redirect()->back();
        }
        return redirect()->back();
    }
}
