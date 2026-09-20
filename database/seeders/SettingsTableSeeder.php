<?php

namespace Database\Seeders;

use App\Setting;
use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Setting::updateOrCreate([
            'settings_name' => 'General Settings',
        ], [
            'content' => '{"company_name":"AYUGLOBAL","contract_person":"+2348031327202","email":"mohammedabdulhakeem5@gmail.com","phone":"+2348031327202","address_1":"Gwarinpa Abuja","address_2":null,"city":"Abuja","state":"FCT","zip_code":"900108","company_logo":"upload\/company-logo\/e.png"}'
        ]);

        Setting::updateOrCreate([
            'settings_name' => 'System Settings',
        ], [
            'content' => '{"date_format":"d m Y","timezone":"Africa\/Lagos","currency_code":"NGN","currency_symbol":"\u20a6","is_code":"code","is_rtl":"No","currency_position":"Prefix","fixed_asset_schedule_starting_date":"2026-10-01"}'
        ]);
    }
}
