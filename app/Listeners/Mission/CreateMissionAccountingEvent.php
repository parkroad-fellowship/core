<?php

namespace App\Listeners\Mission;

use App\Events\Mission\MissionApproved;
use App\Jobs\Mission\CreateAccountingEventJob;

class CreateMissionAccountingEvent
{
    public function handle(MissionApproved $event): void
    {
        CreateAccountingEventJob::dispatchSync($event->mission->id);
    }
}
