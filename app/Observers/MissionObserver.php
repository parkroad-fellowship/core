<?php

namespace App\Observers;

use App\Enums\PRFMissionStatus;
use App\Jobs\Mission\CreateCohortJob;
use App\Jobs\Mission\GenerateWeatherForecastJob;
use App\Models\Mission;

class MissionObserver
{
    /**
     * Handle the Mission "created" event.
     */
    public function created(Mission $mission): void
    {
        //
    }

    /**
     * Handle the Mission "updated" event.
     */
    public function updated(Mission $mission): void
    {
        if ($mission->wasChanged('status')) {
            if (intval($mission->status) === PRFMissionStatus::APPROVED->value) {

                // If the mission is within 7 days, generate the weather forecast immediately
                $diffInDays = $mission->start_date->diffInDays(now());
                if ($diffInDays < 3) {
                    GenerateWeatherForecastJob::dispatch($mission);
                }
            }
        }

        CreateCohortJob::dispatchSync($mission);
    }

    /**
     * Handle the Mission "deleted" event.
     */
    public function deleted(Mission $mission): void
    {
        //
    }

    /**
     * Handle the Mission "restored" event.
     */
    public function restored(Mission $mission): void
    {
        //
    }

    /**
     * Handle the Mission "force deleted" event.
     */
    public function forceDeleted(Mission $mission): void
    {
        //
    }
}
