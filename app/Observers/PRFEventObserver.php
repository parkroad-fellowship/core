<?php

namespace App\Observers;

use App\Enums\PRFEventType;
use App\Events\PRFEvent\PRFEventCreated;
use App\Events\PRFEvent\PRFEventLocationChanged;
use App\Events\PRFEvent\PRFEventOpenedToMembers;
use App\Models\PRFEvent;

/**
 * Translates PRFEvent lifecycle changes into domain events; side effects live in listeners.
 */
class PRFEventObserver
{
    public function created(PRFEvent $prfEvent): void
    {
        PRFEventCreated::dispatch($prfEvent);
    }

    public function updated(PRFEvent $prfEvent): void
    {
        if ($prfEvent->wasChanged('event_type') && $prfEvent->event_type === PRFEventType::MEMBER) {
            PRFEventOpenedToMembers::dispatch($prfEvent);
        }

        if ($prfEvent->wasChanged(['latitude', 'longitude'])) {
            PRFEventLocationChanged::dispatch($prfEvent);
        }
    }
}
