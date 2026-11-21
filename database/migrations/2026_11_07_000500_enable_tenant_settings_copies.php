<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnableTenantSettingsCopies extends Migration
{
    public function up()
    {
        Schema::hasTable('site_settings');
        DB::statement('ALTER TABLE site_settings DROP INDEX site_settings_key_name_unique');

        $templateSettings = DB::table('settings')->where('business_id', 1)->get();
        $siteSettings = DB::table('site_settings')->whereNull('business_id')->get();

        foreach (DB::table('business_settings')->pluck('id') as $businessId) {
            foreach ($templateSettings as $setting) {
                $exists = DB::table('settings')
                    ->where('business_id', $businessId)
                    ->where('settings_name', $setting->settings_name)
                    ->exists();
                if (!$exists) {
                    $copy = (array) $setting;
                    unset($copy['id']);
                    $copy['business_id'] = $businessId;
                    $copy['created_at'] = $copy['created_at'] ?: now();
                    $copy['updated_at'] = now();
                    DB::table('settings')->insert($copy);
                }
            }

            foreach ($siteSettings as $setting) {
                $exists = DB::table('site_settings')
                    ->where('business_id', $businessId)
                    ->where('key_name', $setting->key_name)
                    ->exists();
                if (!$exists) {
                    DB::table('site_settings')->insert([
                        'key_name' => $setting->key_name,
                        'value' => $setting->value,
                        'business_id' => $businessId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        DB::statement('CREATE UNIQUE INDEX site_settings_tenant_key_unique ON site_settings (business_id, key_name)');
    }

    public function down()
    {
    }
}
