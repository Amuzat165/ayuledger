<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AssignMissingUserRoles extends Migration
{
    public function up()
    {
        $adminRole = DB::table('role_manages')->where('name', 'Admin')->first();
        if (!$adminRole) {
            DB::table('role_manages')->insert([
                'name' => 'Admin',
                'content' => '{}',
                'business_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $adminRoleId = DB::getPdo()->lastInsertId();
        } else {
            $adminRoleId = $adminRole->id;
        }

        $superAdminRole = DB::table('role_manages')->where('name', 'Super Admin')->first();
        if (!$superAdminRole) {
            DB::table('role_manages')->insert([
                'name' => 'Super Admin',
                'content' => '{}',
                'business_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $superAdminRoleId = DB::getPdo()->lastInsertId();
        } else {
            $superAdminRoleId = $superAdminRole->id;
        }

        $validRoleIds = DB::table('role_manages')->pluck('id')->all();

        $users = DB::table('users')->whereNull('deleted_at')->get();
        foreach ($users as $user) {
            if ($user->role_manage_id && in_array((int) $user->role_manage_id, $validRoleIds, true)) {
                continue;
            }

            DB::table('users')->where('id', $user->id)->update([
                'role_manage_id' => strcasecmp($user->email, 'admin@gmail.com') === 0
                    ? $superAdminRoleId
                    : $adminRoleId,
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        // Role assignments are repaired data and are intentionally retained.
    }
}
