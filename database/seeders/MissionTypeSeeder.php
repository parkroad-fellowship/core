<?php

namespace Database\Seeders;

use App\Models\MissionType;
use Illuminate\Database\Seeder;

class MissionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $missionType = [
            'Weekend Challenge',
            'Weekday Service',
            'Sunday Service',
            'Leadership Training',
            'Guidance & Counselling Mission',
            'Community Mission',
            'Mission Training and Development (MTD)',
        ];

        foreach ($missionType as $type) {
            MissionType::factory()->create([
                'name' => $type,
            ]);
        }
    }
}
