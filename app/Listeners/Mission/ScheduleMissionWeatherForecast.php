<?php

namespace App\Listeners\Mission;

use App\Events\Mission\MissionApproved;
use App\Jobs\Mission\GenerateWeatherForecastJob;
use App\Jobs\Mission\GenerateWeatherRecommendationsJob;
use Illuminate\Support\Facades\Bus;

/**
 * Missions starting within three days get their forecast now; the scheduled command
 * picks up the rest as they come into range.
 */
class ScheduleMissionWeatherForecast
{
    public function handle(MissionApproved $event): void
    {
        $daysUntilStart = now()->diffInDays($event->mission->start_date, absolute: false);

        if ($daysUntilStart >= 0 && $daysUntilStart < 3) {
            Bus::chain([
                new GenerateWeatherForecastJob($event->mission),
                new GenerateWeatherRecommendationsJob($event->mission),
            ])->dispatch();
        }
    }
}
