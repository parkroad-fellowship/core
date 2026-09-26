<?php

namespace App\Events\EventSubscription;

use App\Models\EventSubscription;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A member registered for an event.
 */
class EventSubscriptionCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public EventSubscription $eventSubscription,
    ) {}
}
