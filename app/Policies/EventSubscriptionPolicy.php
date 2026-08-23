<?php

namespace App\Policies;

use App\Models\EventSubscription;

class EventSubscriptionPolicy extends BasePolicy
{
    protected string $modelClass = EventSubscription::class;
}
