<?php

namespace App\Listeners\Mission;

use App\Events\Mission\MissionApproved;
use App\Jobs\Mission\NotifyMembersJob;
use App\Jobs\Mission\NotifySchoolOfMissionJob;
use App\Notifications\Mission\MissionApprovedNotification;
use Illuminate\Support\Facades\Bus;

/**
 * Tells the school (SMS) and then all members about a newly approved mission.
 */
class AnnounceApprovedMission
{
    public function handle(MissionApproved $event): void
    {
        Bus::chain([
            new NotifySchoolOfMissionJob($event->mission),
            new NotifyMembersJob(new MissionApprovedNotification($event->mission)),
        ])->dispatch();
    }
}
