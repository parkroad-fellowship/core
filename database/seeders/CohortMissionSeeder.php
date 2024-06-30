<?php

namespace Database\Seeders;

use App\Enums\PRFMissionStatus;
use App\Models\Cohort;
use App\Models\Mission;
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
            $cohortStartDate = $missionEndDate->addDays(
                // Carbon::WEDNESDAY === 3
                match ($missionEndDate->dayOfWeek()) {
                    Carbon::WEDNESDAY => 7,
                    0,1,2 => (Carbon::WEDNESDAY - $missionEndDate->dayOfWeek()) + 1,
                    4,5,6 => ($missionEndDate->dayOfWeek() - Carbon::WEDNESDAY) + 1,
                }
            );

            // Create the Cohort if it doesn't exist
            $cohort = Cohort::updateOrCreate([
                'start_date' => $cohortStartDate->format('Y-m-d'),
            ], [
                'start_date' => $cohortStartDate->format('Y-m-d'),
                'title' => 'Week starting '.$cohortStartDate->format('Y-m-d'),
            ]);

            // Add this mission to that cohort
            $cohort->cohortMissions()->create([
                'mission_id' => $mission->id,
            ]);
        }
    }
}
