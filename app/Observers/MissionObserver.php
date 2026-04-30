<?php

namespace App\Observers;

use App\Enums\PRFMissionStatus;
use App\Jobs\AccountingEvent\EmailFinancialReportJob;
use App\Jobs\Mission\CreateAccountingEventJob;
use App\Jobs\Mission\CreateCohortJob;
use App\Jobs\Mission\GenerateExecutiveSummaryJob;
use App\Jobs\Mission\GenerateWeatherForecastJob;
use App\Jobs\Mission\GenerateWeatherRecommendationsJob;
use App\Jobs\Mission\NotifyMembersJob;
use App\Jobs\Mission\NotifySchoolOfMissionJob;
use App\Jobs\Mission\NotifyWhatsAppGroupJob;
use App\Jobs\Mission\RequestSchoolFeedbackJob;
use App\Jobs\Mission\SendThankYouJob;
use App\Models\Mission;
use App\Notifications\Mission\CancelledMissionNotification;
use App\Notifications\Mission\NewMissionNotification;
use App\Notifications\Mission\PostponedMissionNotification;
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
        // TODO: If the dates of a mission change, recheck conflicts for each subscribed member

        if ($mission->wasChanged('status')) {

            switch (intval($mission->status)) {
                case PRFMissionStatus::APPROVED->value:
                    CreateAccountingEventJob::dispatchSync($mission->id);

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
                        new NotifyMembersJob(new NewMissionNotification($mission)),
                    ])->dispatch();

                    break;
                case PRFMissionStatus::SERVICED->value:
                    RequestSchoolFeedbackJob::dispatch($mission);
                    GenerateExecutiveSummaryJob::dispatch($mission);
                    EmailFinancialReportJob::dispatch($mission->accountingEvent->ulid);
                    SendThankYouJob::dispatch($mission);
                    CreateCohortJob::dispatchSync($mission);
                    // UploadFilesToDriveJob::dispatch($mission->id);
                    break;
                case PRFMissionStatus::POSTPONED->value:
                    GenerateExecutiveSummaryJob::dispatch($mission);
                    EmailFinancialReportJob::dispatch($mission->accountingEvent->ulid);
                    Bus::chain([
                        new NotifyMembersJob(new PostponedMissionNotification(
                            mission: $mission,
                            originalStartDate: $mission->getOriginal('start_date'),
                            originalEndDate: $mission->getOriginal('end_date'),
                        )),
                    ])->dispatch();
                    break;
                case PRFMissionStatus::CANCELLED->value:
                    GenerateExecutiveSummaryJob::dispatch($mission);
                    EmailFinancialReportJob::dispatch($mission->accountingEvent->ulid);
                    Bus::chain([
                        new NotifyMembersJob(new CancelledMissionNotification($mission)),
                    ])->dispatch();
                    break;
            }
        }

        if ($mission->wasChanged('whats_app_link')) {
            NotifyWhatsAppGroupJob::dispatch($mission);
        }

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
