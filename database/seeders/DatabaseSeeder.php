<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\UsersTableSeeder;
use Database\Seeders\SettingsTableSeeder;
use Database\Seeders\ReportDataSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UsersTableSeeder::class);
        $this->call(SettingsTableSeeder::class);
        $this->call(SupportedLanguagesSeeder::class);

        // Resolve foreign keys by business keys instead of assuming fixed IDs.
        $this->call(ReportDataSeeder::class);
        $this->call(TenantDemoSeeder::class);
    }
}
