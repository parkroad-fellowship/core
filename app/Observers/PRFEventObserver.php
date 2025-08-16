<?php

namespace App\Observers;

use App\Enums\PRFEventType;
use App\Jobs\PRFEvent\CreateAccountingEventJob;
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
        CreateAccountingEventJob::dispatchSync($prfEvent->id);

        if ($prfEvent->event_type === PRFEventType::MEMBER->value) {
            NotifyMembersJob::dispatch($prfEvent);
        }

        // Check if the location is set, if not, return.
        if (! $prfEvent->latitude || ! $prfEvent->longitude) {
            return;
        }

        Bus::chain([
            new GenerateWeatherForecastJob($prfEvent),
            new GenerateWeatherRecommendationsJob($prfEvent),
        ])->dispatch();
    }

    /**
     * Handle the PRFEvent "updated" event.
     */
    public function updated(PRFEvent $prfEvent): void
    {
        // Notify members if the event type has changed to "Member"
        if ($prfEvent->wasChanged('event_type') && $prfEvent->event_type === PRFEventType::MEMBER->value) {
            NotifyMembersJob::dispatch($prfEvent);
        }

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
