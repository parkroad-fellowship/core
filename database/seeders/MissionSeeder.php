<?php

namespace Database\Seeders;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Enums\PRFMorphType;
use App\Models\DebriefNote;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionQuestion;
use App\Models\MissionSession;
use App\Models\Soul;
use App\Models\WeatherForecast;
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
            // Attach members
            $mission->missionSubscriptions()->createMany(
                Member::inRandomOrder()->limit(rand(3, 10))->get()->map(function ($member) {
                    return [
                        'member_id' => $member->id,
                        'status' => Arr::random(PRFMissionSubscriptionStatus::getValues()),
                    ];
                })->toArray()
            );

            // // Seed Souls
            // Soul::factory()
            //     ->count(3)
            //     ->create([
            //         'mission_id' => $mission->id,
            //     ]);

            // // Seed Debrief Notes
            // DebriefNote::factory()
            //     ->count(3)
            //     ->create([
            //         'mission_id' => $mission->id,
            //     ]);

            // // Seed Mission Questions
            // MissionQuestion::factory()
            //     ->count(3)
            //     ->create([
            //         'mission_id' => $mission->id,
            //     ]);

            // Seed Weather Forecasts
            foreach (range(1, 3) as $index) {
                WeatherForecast::factory()
                    ->create([
                        'weather_forecastable_id' => $mission->id,
                        'weather_forecastable_type' => PRFMorphType::MISSION->value,
                        'forecast_date' => $mission->start_date->subDays($index - 1),
                    ]);
            }

            // Seed sessions
            $subscriptionMemberIds = $mission->missionSubscriptions()->get(['member_id'])->pluck('member_id');
            MissionSession::factory()
                ->count(3)
                ->create([
                    'mission_id' => $mission->id,
                    'facilitator_id' => $subscriptionMemberIds->random(),
                    'speaker_id' => $subscriptionMemberIds->random(),
                ]);
        });
    }
}
