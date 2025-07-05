<?php

namespace App\Observers;

use App\Jobs\MissionSubscription\IdentifyConflictJob;
use App\Jobs\MissionSubscription\MarkConflictsJob;
use App\Jobs\MissionSubscription\NotifyMemberJob;
use App\Models\MissionSubscription;
use Illuminate\Support\Facades\Bus;

class MissionSubscriptionObserver
{
    /**
     * Handle the MissionSubscription "created" event.
     */
    public function created(MissionSubscription $missionSubscription): void
    {
        // Notify the member about their subscription
        NotifyMemberJob::dispatch($missionSubscription);

        // Check for conflicts
        IdentifyConflictJob::dispatch($missionSubscription);
    }

    /**
     * Handle the MissionSubscription "updated" event.
     */
    public function updated(MissionSubscription $missionSubscription): void
    {
        // Only notify if the status has actually changed
        if ($missionSubscription->wasChanged('status')) {
            Bus::chain([
                MarkConflictsJob::dispatch($missionSubscription),
                NotifyMemberJob::dispatch($missionSubscription),
            ]);
        } else {
            // If status didn't change, just check for conflicts
            MarkConflictsJob::dispatch($missionSubscription);
        }
    }

    /**
     * Handle the MissionSubscription "deleted" event.
     */
    public function deleted(MissionSubscription $missionSubscription): void
    {
        //
    }

    /**
     * Handle the MissionSubscription "restored" event.
     */
    public function restored(MissionSubscription $missionSubscription): void
    {
        //
    }

    /**
     * Handle the MissionSubscription "force deleted" event.
     */
    public function forceDeleted(MissionSubscription $missionSubscription): void
    {
        //
    }
}
