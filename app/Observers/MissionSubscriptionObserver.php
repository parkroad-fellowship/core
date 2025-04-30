<?php

namespace App\Observers;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Jobs\MissionSubscription\NotifyMemberJob;
use App\Models\MissionSubscription;

class MissionSubscriptionObserver
{
    /**
     * Handle the MissionSubscription "created" event.
     */
    public function created(MissionSubscription $missionSubscription): void
    {
        if ($missionSubscription->mission_subscription_status == PRFMissionSubscriptionStatus::APPROVED) {
            NotifyMemberJob::dispatch($missionSubscription);
        }
    }

    /**
     * Handle the MissionSubscription "updated" event.
     */
    public function updated(MissionSubscription $missionSubscription): void
    {
        if ($missionSubscription->wasChanged('status')) {

            switch ($missionSubscription->mission_subscription_status) {
                case PRFMissionSubscriptionStatus::APPROVED:
                    NotifyMemberJob::dispatch($missionSubscription);
                    break;
            }
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
