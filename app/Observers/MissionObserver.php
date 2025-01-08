<?php

namespace App\Observers;

use App\Enums\PRFMissionStatus;
use App\Jobs\Mission\CreateCohortJob;
use App\Jobs\Mission\NotifyMembersJob;
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
            $newStatus = $mission->status;

            if ($newStatus === PRFMissionStatus::APPROVED->value) {
                NotifyMembersJob::dispatch($mission);
            }
        }
        CreateCohortJob::dispatch($mission->withoutRelations());
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
