<?php

namespace App\Listeners\EventSubscription;

use App\Events\EventSubscription\EventSubscriptionCreated;
use App\Jobs\EventSubscription\NotifyEventHandlersJob;

class NotifyEventHandlers
{
    public function handle(EventSubscriptionCreated $event): void
    {
        NotifyEventHandlersJob::dispatch($event->eventSubscription->id);
    }
}
