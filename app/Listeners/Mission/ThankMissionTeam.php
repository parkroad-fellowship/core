<?php

namespace App\Listeners\Mission;

use App\Events\Mission\MissionServiced;
use App\Jobs\Mission\SendThankYouJob;

class ThankMissionTeam
{
    public function handle(MissionServiced $event): void
    {
        SendThankYouJob::dispatch($event->mission);
    }
}
