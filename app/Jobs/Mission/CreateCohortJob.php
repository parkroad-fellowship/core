<?php

namespace App\Jobs\Mission;

use App\Enums\PRFMissionStatus;
use App\Models\Cohort;
use App\Models\Mission;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;

class CreateCohortJob
{
    use Dispatchable;

    public function __construct(
        public Mission $mission,
    ) {}

    public function handle(): void
    {
        $mission = $this->mission;
        // Attach missions where souls were won to a cohort
        // Set the cohort start date to the Wednesday of the week after the mission ends
        // If the mission has been serviced, create a cohort for it
        if ($mission->status->is(PRFMissionStatus::SERVICED) && $mission->souls()->count() > 0) {
            $missionEndDate = $mission->end_date->copy();
            $dayOfWeek = $missionEndDate->dayOfWeek;
            $cohortStartDate = $missionEndDate->addDays(
                // Carbon::WEDNESDAY === 3
                match ($dayOfWeek) {
                    0, 1, 2 => Carbon::WEDNESDAY - $dayOfWeek,
                    4, 5, 6 => $dayOfWeek - Carbon::WEDNESDAY + 1,
                    default => 7,
                },
            );

            // Create the Cohort if it doesn't exist
            $cohort = Cohort::updateOrCreate([
                'start_date' => $cohortStartDate->format('Y-m-d'),
            ], [
                'start_date' => $cohortStartDate->format('Y-m-d'),
                'title' => 'Week starting ' . $cohortStartDate->format('Y-m-d'),
            ]);

            // Add this mission to that cohort
            $cohort->cohortMissions()->updateOrCreate([
                'mission_id' => $mission->id,
            ], [
                'mission_id' => $mission->id,
            ]);
        }
    }
}
