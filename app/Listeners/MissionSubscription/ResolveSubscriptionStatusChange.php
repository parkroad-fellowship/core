<?php

namespace App\Listeners\MissionSubscription;

use App\Events\MissionSubscription\MissionSubscriptionStatusChanged;
use App\Jobs\MissionSubscription\MarkConflictsJob;
use App\Jobs\MissionSubscription\NotifyMemberJob;
use Illuminate\Support\Facades\Bus;

/**
 * Marks the member's clashing pending subscriptions as conflicts, then tells them about the new status.
 */
class ResolveSubscriptionStatusChange
{
    public function handle(MissionSubscriptionStatusChanged $event): void
    {
        Bus::chain([
            new MarkConflictsJob($event->missionSubscription),
            new NotifyMemberJob($event->missionSubscription),
        ])->dispatch();
    }
}
