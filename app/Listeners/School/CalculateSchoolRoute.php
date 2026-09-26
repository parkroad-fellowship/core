<?php

namespace App\Listeners\School;

use App\Events\School\SchoolLocationChanged;
use App\Jobs\School\CalculateRouteJob;

class CalculateSchoolRoute
{
    public function handle(SchoolLocationChanged $event): void
    {
        CalculateRouteJob::dispatch($event->school);
    }
}
