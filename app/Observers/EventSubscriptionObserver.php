<?php

namespace App\Observers;

use App\Events\EventSubscription\EventSubscriptionCreated;
use App\Models\EventSubscription;

/**
 * Translates EventSubscription lifecycle changes into domain events; side effects live in listeners.
 */
class EventSubscriptionObserver
{
    public function created(EventSubscription $eventSubscription): void
    {
        EventSubscriptionCreated::dispatch($eventSubscription);
    }
}
