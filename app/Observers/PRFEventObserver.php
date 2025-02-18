<?php

namespace App\Observers;

use App\Jobs\PRFEvent\GenerateWeatherForecastJob;
use App\Jobs\PRFEvent\GenerateWeatherRecommendationsJob;
use App\Jobs\PRFEvent\NotifyMembersJob;
use App\Models\PRFEvent;
use Illuminate\Support\Facades\Bus;

class PRFEventObserver
{
    /**
     * Handle the PRFEvent "created" event.
     */
    public function created(PRFEvent $prfEvent): void
    {
        // Check if the location is set, if not, return.
        if (! $prfEvent->latitude || ! $prfEvent->longitude) {
            return;
        }

        Bus::chain([
            new GenerateWeatherForecastJob($prfEvent),
            new GenerateWeatherRecommendationsJob($prfEvent),
            // new NotifyMembersJob($prfEvent),
        ])->dispatch();
    }

    /**
     * Handle the PRFEvent "updated" event.
     */
    public function updated(PRFEvent $prfEvent): void
    {
        // Check if the location is set, if not, return.
        if (! $prfEvent->latitude || ! $prfEvent->longitude) {
            return;
        }

        // Check if the latitude or longitude has changed. If not, return.
        if (! $prfEvent->wasChanged(['latitude', 'longitude'])) {
            return;
        }

        Bus::chain([
            new GenerateWeatherForecastJob($prfEvent),
            new GenerateWeatherRecommendationsJob($prfEvent),
            // new NotifyMembersJob($prfEvent),
        ])->dispatch();
    }

    /**
     * Handle the PRFEvent "deleted" event.
     */
    public function deleted(PRFEvent $prfEvent): void
    {
        //
    }

    /**
     * Handle the PRFEvent "restored" event.
     */
    public function restored(PRFEvent $prfEvent): void
    {
        //
    }

    /**
     * Handle the PRFEvent "force deleted" event.
     */
    public function forceDeleted(PRFEvent $prfEvent): void
    {
        //
    }
}
