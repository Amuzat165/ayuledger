<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUserLimitsToSubscriptionPlans extends Migration
{
    public function up()
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('max_users')->default(5)->after('yearly_price');
        });

        DB::table('subscription_plans')->where('slug', 'basic')->update(['max_users' => 5]);
        DB::table('subscription_plans')->where('slug', 'standard')->update(['max_users' => 15]);
        DB::table('subscription_plans')->where('slug', 'premium')->update(['max_users' => 50]);
    }

    public function down()
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('max_users');
        });
    }
}
