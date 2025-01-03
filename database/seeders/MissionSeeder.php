<?php

namespace Database\Seeders;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\DebriefNote;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionQuestion;
use App\Models\Soul;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class MissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $missions = Mission::factory()->count(3)->create();

        $missions->each(function ($mission) {
            // // Attach members
            // $mission->missionSubscriptions()->createMany(
            //     Member::inRandomOrder()->limit(rand(3, 10))->get()->map(function ($member) {
            //         return [
            //             'member_id' => $member->id,
            //             'status' => Arr::random(PRFMissionSubscriptionStatus::getValues()),
            //         ];
            //     })->toArray()
            // );

            // Seed Souls
            Soul::factory()
                ->count(3)
                ->create([
                    'mission_id' => $mission->id,
                ]);

            // Seed Debrief Notes
            DebriefNote::factory()
                ->count(3)
                ->create([
                    'mission_id' => $mission->id,
                ]);
            // Seed Mission Questions
            MissionQuestion::factory()
                ->count(3)
                ->create([
                    'mission_id' => $mission->id,
                ]);
        });
    }
}
