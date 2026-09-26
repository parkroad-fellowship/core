<?php

namespace App\Listeners\PRFEvent;

use App\Events\PRFEvent\PRFEventCreated;
use App\Events\PRFEvent\PRFEventLocationChanged;
use App\Jobs\PRFEvent\GenerateWeatherForecastJob;
use App\Jobs\PRFEvent\GenerateWeatherRecommendationsJob;
use Illuminate\Support\Facades\Bus;

class SchedulePRFEventWeatherForecast
{
    public function handle(PRFEventCreated|PRFEventLocationChanged $event): void
    {
        $prfEvent = $event->prfEvent;

        if (!$prfEvent->latitude || !$prfEvent->longitude) {
            return;
        }

        Bus::chain([
            new GenerateWeatherForecastJob($prfEvent),
            new GenerateWeatherRecommendationsJob($prfEvent),
        ])->dispatch();
    }
}
