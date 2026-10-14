<?php

namespace App\Listeners\Mission;

use App\Events\Mission\MissionServiced;
use App\Jobs\Mission\CreateCohortJob;

class CreateMissionCohort
{
    public function handle(MissionServiced $event): void
    {
        CreateCohortJob::dispatchSync($event->mission);
    }
}
