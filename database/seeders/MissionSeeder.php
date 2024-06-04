<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Mission;
use Illuminate\Database\Seeder;

class MissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      $missions =  Mission::factory()->count(10)->create();

      $missions->each(function ($mission) {
        // Attach members
        $mission->missionSubscriptions()->createMany(
            Member::inRandomOrder()->limit(rand(3, 10))->get()->map(function ($member) {
                return ['member_id' => $member->id];
            })->toArray()
        );
    });
    }
}
