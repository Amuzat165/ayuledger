<?php

namespace Database\Seeders;

use App\Language;
use Illuminate\Database\Seeder;

class SupportedLanguagesSeeder extends Seeder
{
    public static function languageDefinitions()
    {
        return [
            ['name' => 'arabic', 'code' => 'ar', 'country_name' => 'Multiple countries'],
            ['name' => 'portuguese', 'code' => 'pt', 'country_name' => 'Multiple countries'],
        ];
    }

    public function run()
    {
        foreach (self::languageDefinitions() as $language) {
            Language::firstOrCreate(
                ['code' => $language['code']],
                $language + ['is_default' => false]
            );
        }
    }
}
