<?php

namespace App\Listeners\Mission;

use App\Events\Mission\MissionCancelled;
use App\Events\Mission\MissionPostponed;
use App\Jobs\Mission\NotifyMembersJob;
use App\Notifications\Mission\MissionCancelledNotification;
use App\Notifications\Mission\MissionPostponedNotification;

/**
 * Tells all members when a mission they may have planned for is postponed or cancelled.
 */
class NotifyMembersOfMissionChange
{
    public function handle(MissionPostponed|MissionCancelled $event): void
    {
        $notification = $event instanceof MissionPostponed
            ? new MissionPostponedNotification(
                mission: $event->mission,
                originalStartDate: $event->originalStartDate,
                originalEndDate: $event->originalEndDate,
            )
            : new MissionCancelledNotification($event->mission);

        NotifyMembersJob::dispatch($notification);
    }
}
