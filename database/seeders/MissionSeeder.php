<?php

namespace Database\Seeders;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Enums\PRFMorphType;
use App\Models\DebriefNote;
use App\Models\Expense;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionExpense;
use App\Models\MissionQuestion;
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

            // Seed Mission Expenses
            MissionExpense::factory()
                ->count(1)
                ->create([
                    'mission_id' => $mission->id,
                ]);

            // Seed Expenses
            Expense::factory()
                ->count(3)
                ->create([
                    'expenseable_id' => MissionExpense::query()->where('mission_id', $mission->id)->first()->getKey(),
                    'expenseable_type' => PRFMorphType::MISSION_EXPENSE->value,
                ]);

            // Seed Weather Forecasts
            foreach (range(1, 3) as $index) {
                WeatherForecast::factory()
                    ->create([
                        'mission_id' => $mission->id,
                        'forecast_date' => $mission->start_date->subDays($index),
                    ]);
            }
        });
    }
}
