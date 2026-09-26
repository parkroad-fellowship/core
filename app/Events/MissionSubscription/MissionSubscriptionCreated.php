<?php

namespace App\Events\MissionSubscription;

use App\Models\MissionSubscription;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A member subscribed to a mission.
 */
class MissionSubscriptionCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public MissionSubscription $missionSubscription,
    ) {}
}
