<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSubscriptionResourceLimits extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('subscription_plans', 'max_branches')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->unsignedInteger('max_branches')->nullable()->after('max_users');
            });
        }

        if (!Schema::hasColumn('business_settings', 'max_users_override')) {
            Schema::table('business_settings', function (Blueprint $table) {
                $table->unsignedInteger('max_users_override')->nullable()->after('subscription_plan');
            });
        }

        if (!Schema::hasColumn('business_settings', 'max_branches_override')) {
            Schema::table('business_settings', function (Blueprint $table) {
                $table->unsignedInteger('max_branches_override')->nullable()->after('max_users_override');
            });
        }

        DB::table('subscription_plans')->where('slug', 'basic')->update(['max_branches' => 2]);
        DB::table('subscription_plans')->where('slug', 'standard')->update(['max_branches' => 5]);
        DB::table('subscription_plans')->where('slug', 'premium')->update(['max_branches' => null]);
    }

    public function down()
    {
        if (Schema::hasColumn('business_settings', 'max_branches_override')) {
            Schema::table('business_settings', function (Blueprint $table) {
                $table->dropColumn('max_branches_override');
            });
        }

        if (Schema::hasColumn('business_settings', 'max_users_override')) {
            Schema::table('business_settings', function (Blueprint $table) {
                $table->dropColumn('max_users_override');
            });
        }

        if (Schema::hasColumn('subscription_plans', 'max_branches')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->dropColumn('max_branches');
            });
        }
    }
}
