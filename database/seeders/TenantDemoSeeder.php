<?php

namespace Database\Seeders;

use App\Branch;
use App\BusinessSetting;
use App\Models\Inventory;
use App\Models\InventoryType;
use App\RoleManage;
use App\Tenant;
use App\TenantMembership;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TenantDemoSeeder extends Seeder
{
    private $password = '123456';

    public function run()
    {
        $superAdminRole = RoleManage::firstOrCreate(
            ['name' => 'Super Admin'],
            ['content' => '{}', 'create_by' => 'System']
        );

        $superAdmin = User::withTrashed()->updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($this->password),
                'role_manage_id' => $superAdminRole->id,
                'create_by' => 'System',
                'deleted_at' => null,
            ]
        );

        $tenantAdminRole = RoleManage::firstOrCreate(
            ['name' => 'Admin'],
            ['content' => '{}', 'create_by' => 'System']
        );

        $this->business(
            'Aurora Retail Limited',
            'aurora-retail',
            'aurora.admin@example.com',
            'Aurora Administrator',
            $tenantAdminRole,
            101,
            'Main Store'
        );

        $this->business(
            'Bluecrest Services',
            'bluecrest-services',
            'bluecrest.admin@example.com',
            'Bluecrest Administrator',
            $tenantAdminRole,
            201,
            'Lagos Office'
        );

        $legacyTenant = Tenant::where('slug', 'ayuglobal')->first();
        if ($legacyTenant) {
            TenantMembership::updateOrCreate(
                ['tenant_id' => $legacyTenant->id, 'user_id' => $superAdmin->id],
                ['role_manage_id' => $superAdminRole->id, 'status' => 'active', 'business_id' => $legacyTenant->id]
            );
        }
    }

    private function business($businessName, $slug, $email, $ownerName, RoleManage $role, $stockCode, $branchName)
    {
        $tenant = Tenant::updateOrCreate(
            ['slug' => $slug],
            ['name' => $businessName, 'status' => 'active']
        );

        BusinessSetting::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'id' => $tenant->id,
                'business_name' => $businessName,
                'business_email' => $email,
                'phone' => '+2348000000000',
                'address' => 'Nigeria',
                'subscription_plan' => 'demo',
                'subscription_status' => 'active',
                'registration_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => User::where('email', 'admin@gmail.com')->value('id'),
            ]
        );

        $owner = User::withTrashed()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $ownerName,
                'password' => Hash::make($this->password),
                'role_manage_id' => $role->id,
                'business_id' => $tenant->id,
                'create_by' => 'System',
                'deleted_at' => null,
            ]
        );

        TenantMembership::updateOrCreate(
            ['tenant_id' => $tenant->id, 'user_id' => $owner->id],
            [
                'role_manage_id' => $role->id,
                'status' => 'active',
                'business_id' => $tenant->id,
            ]
        );

        if (Branch::query()->where('tenant_id', $tenant->id)->count() === 0) {
            DB::table('branches')->insert([
                'name' => $branchName,
                'location' => 'Demo address',
                'description' => 'Demo branch',
                'tenant_id' => $tenant->id,
                'business_id' => $tenant->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $type = DB::table('inventory_types')->where('tenant_id', $tenant->id)->first();
        if (!$type) {
            $typeId = DB::table('inventory_types')->insertGetId([
                'name' => 'General Goods ' . $tenant->id,
                'tenant_id' => $tenant->id,
                'business_id' => $tenant->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $typeId = $type->id;
        }

        $branch = Branch::where('tenant_id', $tenant->id)->first();
        if (!DB::table('inventories')->where('tenant_id', $tenant->id)->where('description', $businessName . ' Demo Product')->exists()) {
            DB::table('inventories')->insert([
                'type_id' => $typeId,
                'branch_id' => $branch->id,
                'description' => 'Tenant-isolated demo inventory',
                'sub_item' => $businessName . ' Demo Product',
                'unit_of_measurement' => 'piece',
                'unit_cost' => 100,
                'sale_price' => 150,
                'quantity_at_hand' => $stockCode,
                'account' => 'Sales',
                'business_id' => $tenant->id,
                'tenant_id' => $tenant->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
