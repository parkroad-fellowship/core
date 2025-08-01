<?php

namespace App\Observers;

use App\Jobs\EventSubscription\NotifyEventHandlersJob;
use App\Models\EventSubscription;

class EventSubscriptionObserver
{
    /**
     * Handle the EventSubscription "created" event.
     */
    public function created(EventSubscription $eventSubscription): void
    {
        NotifyEventHandlersJob::dispatch($eventSubscription->id);
    }

    /**
     * Handle the EventSubscription "updated" event.
     */
    public function updated(EventSubscription $eventSubscription): void
    {
        //
    }

    /**
     * Handle the EventSubscription "deleted" event.
     */
    public function deleted(EventSubscription $eventSubscription): void
    {
        //
    }

    /**
     * Handle the EventSubscription "restored" event.
     */
    public function restored(EventSubscription $eventSubscription): void
    {
        //
    }

    /**
     * Handle the EventSubscription "force deleted" event.
     */
    public function forceDeleted(EventSubscription $eventSubscription): void
    {
        //
    }
}
