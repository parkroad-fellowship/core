<?php

namespace App\Observers;

use App\Events\MissionSubscription\MissionSubscriptionCreated;
use App\Events\MissionSubscription\MissionSubscriptionStatusChanged;
use App\Models\MissionSubscription;

/**
 * Translates MissionSubscription lifecycle changes into domain events; side effects live in listeners.
 */
class MissionSubscriptionObserver
{
    public function created(MissionSubscription $missionSubscription): void
    {
        MissionSubscriptionCreated::dispatch($missionSubscription);
    }

    public function updated(MissionSubscription $missionSubscription): void
    {
        if ($missionSubscription->wasChanged('status')) {
            MissionSubscriptionStatusChanged::dispatch($missionSubscription);
        }
    }
}
