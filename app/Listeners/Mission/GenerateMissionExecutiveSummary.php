<?php

namespace App\Listeners\Mission;

use App\Events\Mission\MissionCancelled;
use App\Events\Mission\MissionPostponed;
use App\Events\Mission\MissionServiced;
use App\Jobs\Mission\GenerateExecutiveSummaryJob;

class GenerateMissionExecutiveSummary
{
    public function handle(MissionServiced|MissionPostponed|MissionCancelled $event): void
    {
        GenerateExecutiveSummaryJob::dispatch($event->mission);
    }
}
