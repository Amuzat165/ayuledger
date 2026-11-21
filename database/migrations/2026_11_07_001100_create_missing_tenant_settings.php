<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMissingTenantSettings extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('business_settings') || !Schema::hasTable('settings')) {
            return;
        }

        $templates = DB::table('settings')->where('business_id', 1)->get();
        if (!$templates->count()) {
            $templates = collect([
                (object) [
                    'settings_name' => 'General Settings',
                    'content' => json_encode([
                        'company_name' => 'AYUGLOBAL',
                        'contract_person' => '',
                        'email' => '',
                        'phone' => '',
                        'address_1' => '',
                        'address_2' => null,
                        'city' => '',
                        'state' => '',
                        'zip_code' => '',
                        'company_logo' => null,
                        'header_image' => null,
                        'vat_percentage' => 0,
                        'discount_enabled' => false,
                        'company_color' => '#00897B',
                    ]),
                ],
                (object) [
                    'settings_name' => 'System Settings',
                    'content' => json_encode([
                        'date_format' => 'd m Y',
                        'timezone' => 'Africa/Lagos',
                        'currency_code' => 'NGN',
                        'currency_symbol' => 'NGN',
                        'is_code' => 'code',
                        'is_rtl' => 'No',
                        'currency_position' => 'Prefix',
                        'fixed_asset_schedule_starting_date' => '2026-01-01',
                    ]),
                ],
            ]);
        }

        foreach (DB::table('business_settings')->pluck('id') as $businessId) {
            foreach ($templates as $template) {
                $exists = DB::table('settings')
                    ->where('business_id', $businessId)
                    ->where('settings_name', $template->settings_name)
                    ->exists();

                if (!$exists) {
                    DB::table('settings')->insert([
                        'settings_name' => $template->settings_name,
                        'content' => $template->content,
                        'business_id' => $businessId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down()
    {
    }
}
