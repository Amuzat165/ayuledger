<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTenantMembershipsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tenant_memberships')) {
            Schema::create('tenant_memberships', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('tenant_id');
                // The existing users and role_manages primary keys are INT UNSIGNED.
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('role_manage_id')->nullable();
                $table->string('status', 16)->default('active');
                $table->timestamps();

                $table->unique(['tenant_id', 'user_id']);
                $table->index(['user_id', 'status']);
                $table->index(['tenant_id', 'status']);
                $table->index('role_manage_id');

                $table->foreign('tenant_id')
                    ->references('id')->on('tenants')
                    ->onDelete('restrict')->onUpdate('cascade');
                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->onDelete('restrict')->onUpdate('cascade');
                $table->foreign('role_manage_id')
                    ->references('id')->on('role_manages')
                    ->onDelete('restrict')->onUpdate('cascade');
            });
        }

        $tenant = DB::table('tenants')->where('slug', 'ayuglobal')->first();
        if (!$tenant) {
            $now = now();
            DB::table('tenants')->insert([
                'name' => 'AYUGLOBAL',
                'slug' => 'ayuglobal',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $tenant = DB::table('tenants')->where('slug', 'ayuglobal')->first();
        }

        $activeUsers = DB::table('users')->whereNull('deleted_at')->orderBy('id')->get(['id', 'role_manage_id']);
        if ($activeUsers->count() !== 1) {
            throw new RuntimeException('Tenant bootstrap requires exactly one non-deleted application user.');
        }

        $user = $activeUsers->first();
        $membershipExists = DB::table('tenant_memberships')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$membershipExists) {
            $now = now();
            DB::table('tenant_memberships')->insert([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role_manage_id' => $user->role_manage_id,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('tenant_memberships');
    }
}
