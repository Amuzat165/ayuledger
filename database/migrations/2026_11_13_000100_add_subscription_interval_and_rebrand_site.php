<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSubscriptionIntervalAndRebrandSite extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('business_settings', 'subscription_interval')) {
            Schema::table('business_settings', function (Blueprint $table) {
                $table->string('subscription_interval', 10)->default('monthly')->after('subscription_plan');
            });
        }

        if (Schema::hasTable('site_settings')) {
            DB::table('site_settings')->where('key_name', 'brand_name')->whereNull('business_id')
                ->where('value', 'AYUGLOBAL')->update(['value' => 'AYULEDGERS', 'updated_at' => now()]);
        }
    }

    public function down()
    {
        if (Schema::hasColumn('business_settings', 'subscription_interval')) {
            Schema::table('business_settings', function (Blueprint $table) {
                $table->dropColumn('subscription_interval');
            });
        }
    }
}
