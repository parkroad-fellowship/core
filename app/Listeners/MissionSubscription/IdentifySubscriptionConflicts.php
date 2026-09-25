<?php

namespace App\Listeners\MissionSubscription;

use App\Events\MissionSubscription\MissionSubscriptionCreated;
use App\Jobs\MissionSubscription\IdentifyConflictJob;

class IdentifySubscriptionConflicts
{
    public function handle(MissionSubscriptionCreated $event): void
    {
        IdentifyConflictJob::dispatch($event->missionSubscription);
    }
}
