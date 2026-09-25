<?php

namespace App\Listeners\MissionSubscription;

use App\Events\MissionSubscription\MissionSubscriptionCreated;
use App\Jobs\MissionSubscription\NotifyMemberJob;

class NotifyMemberOfNewSubscription
{
    public function handle(MissionSubscriptionCreated $event): void
    {
        NotifyMemberJob::dispatch($event->missionSubscription);
    }
}
