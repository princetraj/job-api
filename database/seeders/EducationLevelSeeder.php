<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EducationLevel;
use Illuminate\Support\Facades\DB;

class EducationLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $educationLevels = [
            ['name' => 'SSLC', 'status' => 'active', 'order' => 1],
            ['name' => 'Plus Two', 'status' => 'active', 'order' => 2],
            ['name' => 'Diploma', 'status' => 'active', 'order' => 3],
            ['name' => 'Undergraduate (UG)', 'status' => 'active', 'order' => 4],
            ['name' => 'Postgraduate (PG)', 'status' => 'active', 'order' => 5],
            ['name' => 'Doctorate (PhD)', 'status' => 'active', 'order' => 6],
            ['name' => 'Certificate Course', 'status' => 'active', 'order' => 7],
            ['name' => 'Other', 'status' => 'active', 'order' => 8],
        ];

        foreach ($educationLevels as $level) {
            EducationLevel::firstOrCreate(
                ['name' => $level['name']],
                $level
            );
        }

        $this->command->info('Education levels seeded successfully!');
    }
}
