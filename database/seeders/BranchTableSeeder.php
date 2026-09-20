<?php
namespace Database\Seeders;
use App\Branch;
use Illuminate\Database\Seeder;

class BranchTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $legacyNames = [
            'HQ' => 'Kaduna Branch',
            'Head Office' => 'Abuja Head Office',
            'Rajshahi Branch' => 'Port Harcourt Branch',
            'Sirajgonj Branch' => 'Ibadan Branch',
            'Bogra Branch' => 'Enugu Branch',
        ];

        foreach ($legacyNames as $oldName => $newName) {
            Branch::where('name', $oldName)->update(['name' => $newName]);
        }

        foreach ([
            ['name' => 'Abuja Head Office', 'location' => 'Abuja, Nigeria'],
            ['name' => 'Lagos Branch', 'location' => 'Lagos, Nigeria'],
            ['name' => 'Kano Branch', 'location' => 'Kano, Nigeria'],
            ['name' => 'Port Harcourt Branch', 'location' => 'Port Harcourt, Nigeria'],
            ['name' => 'Ibadan Branch', 'location' => 'Ibadan, Nigeria'],
            ['name' => 'Enugu Branch', 'location' => 'Enugu, Nigeria'],
            ['name' => 'Kaduna Branch', 'location' => 'Kaduna, Nigeria'],
        ] as $branch) {
            Branch::updateOrCreate(['name' => $branch['name']], [
                'location' => $branch['location'],
                'description' => 'Nigerian test branch',
            ]);
        }
    }
}
