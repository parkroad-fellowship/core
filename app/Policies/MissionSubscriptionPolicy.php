<?php

namespace App\Policies;

use App\Models\MissionSubscription;

class MissionSubscriptionPolicy extends BasePolicy
{
    protected string $modelClass = MissionSubscription::class;
}
