<?php

namespace App\Observers;

use App\Enums\PRFMissionStatus;
use App\Jobs\Mission\CreateCohortJob;
use App\Jobs\Mission\EmailFinancialReportJob;
use App\Jobs\Mission\GenerateExecutiveSummaryJob;
use App\Jobs\Mission\GenerateWeatherForecastJob;
use App\Jobs\Mission\GenerateWeatherRecommendationsJob;
use App\Jobs\Mission\NotifyMembersJob;
use App\Jobs\Mission\NotifySchoolOfMissionJob;
use App\Models\Mission;
use Illuminate\Support\Facades\Bus;

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

            switch (intval($mission->status)) {
                case PRFMissionStatus::APPROVED->value:
                    // If the mission is within 7 days, generate the weather forecast immediately
                    $diffInDays = $mission->start_date->diffInDays(now());
                    if ($diffInDays < 3) {
                        Bus::chain([
                            new GenerateWeatherForecastJob($mission),
                            new GenerateWeatherRecommendationsJob($mission),
                        ])->dispatch();
                    }

                    Bus::chain([
                        new NotifySchoolOfMissionJob($mission),
                        new NotifyMembersJob($mission),
                    ])->dispatch();

                    break;
                case PRFMissionStatus::SERVICED->value:
                    GenerateExecutiveSummaryJob::dispatch($mission);
                    EmailFinancialReportJob::dispatch($mission);
                    break;
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
