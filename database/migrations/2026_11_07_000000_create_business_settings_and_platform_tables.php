<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBusinessSettingsAndPlatformTables extends Migration
{
    public function up()
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->string('business_name');
            $table->string('business_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('currency', 10)->default('NGN');
            $table->string('timezone', 100)->default('Africa/Lagos');
            $table->string('subscription_plan')->default('trial');
            $table->string('subscription_status', 20)->default('pending_payment');
            $table->string('registration_status', 20)->default('pending');
            $table->date('subscription_starts_at')->nullable();
            $table->date('subscription_expires_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('payment_amount', 15, 2)->nullable();
            $table->timestamp('payment_submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        $now = now();
        foreach (DB::table('tenants')->orderBy('id')->get() as $tenant) {
            DB::table('business_settings')->insert([
                'id' => $tenant->id,
                'tenant_id' => $tenant->id,
                'business_name' => $tenant->name,
                'business_email' => null,
                'subscription_plan' => 'active',
                'subscription_status' => 'active',
                'registration_status' => 'approved',
                'approved_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::create('business_feedback', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('status', 20)->default('new');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business_settings')->onDelete('cascade');
        });

        Schema::create('business_payment_verifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id');
            $table->string('reference');
            $table->string('payment_method')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('paid_on');
            $table->string('proof_path')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business_settings')->onDelete('cascade');
        });

        foreach (['branches', 'inventory_types', 'inventories', 'tenant_memberships'] as $tableName) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'business_id')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->unsignedBigInteger('business_id')->nullable()->after('tenant_id');
                $table->index('business_id', $tableName . '_business_id_index');
            });
        }

        foreach (DB::table('tenants')->get(['id']) as $tenant) {
            if (DB::table('business_settings')->where('id', $tenant->id)->doesntExist()) {
                continue;
            }
            foreach (['branches', 'inventory_types', 'inventories', 'tenant_memberships'] as $tableName) {
                if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'business_id')) {
                    DB::table($tableName)->where('tenant_id', $tenant->id)->update(['business_id' => $tenant->id]);
                }
            }
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'business_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('business_id')->nullable()->after('id');
                $table->index('business_id');
            });
            DB::table('users')->update(['business_id' => DB::raw('(SELECT MIN(business_id) FROM tenant_memberships WHERE tenant_memberships.user_id = users.id)')]);
        }
    }

    public function down()
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'business_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['business_id']);
                $table->dropColumn('business_id');
            });
        }
        foreach (['tenant_memberships', 'inventories', 'inventory_types', 'branches'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'business_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropIndex($tableName . '_business_id_index');
                    $table->dropColumn('business_id');
                });
            }
        }
        Schema::dropIfExists('business_payment_verifications');
        Schema::dropIfExists('business_feedback');
        Schema::dropIfExists('business_settings');
    }
}
