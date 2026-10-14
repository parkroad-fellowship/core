<?php

namespace App\Listeners\Mission;

use App\Events\Mission\MissionServiced;
use App\Jobs\Mission\RequestSchoolFeedbackJob;

class RequestSchoolFeedback
{
    public function handle(MissionServiced $event): void
    {
        RequestSchoolFeedbackJob::dispatch($event->mission);
    }
}
