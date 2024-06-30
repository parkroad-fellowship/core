<?php

namespace Database\Seeders;

use App\Enums\PRFMissionStatus;
use App\Models\Cohort;
use App\Models\Mission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CohortMissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Mission::query()
            ->where('status', PRFMissionStatus::SERVICED)
            ->has('souls')
            ->cursor() as $mission) {
            // Attach missions where souls were won to a cohort
            // Set the cohort start date to the Wednesday of the week after the mission ends
            $missionEndDate = Carbon::parse($mission->end_date);
            $cohortStartDate = Carbon::parse($mission->end_date)->addDays(
                // 5 because $carbon->dayOfWeekIso for wednesday is always 5
                5 -  $missionEndDate->dayOfWeekIso
            );

            // Create the Cohort if it doesn't exist
            $cohort = Cohort::updateOrCreate([
                'start_date' => $cohortStartDate->format('Y-m-d'),
            ], [
                'start_date' => $cohortStartDate->format('Y-m-d'),
                'title' => 'Week starting ' . $cohortStartDate->format('Y-m-d'),
            ]);

            // Add this mission to that cohort
            $cohort->cohortMissions()->create([
                'mission_id' => $mission->id,
            ]);
        }
    }
}
