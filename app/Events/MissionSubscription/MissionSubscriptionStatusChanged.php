<?php

namespace App\Events\MissionSubscription;

use App\Models\MissionSubscription;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A subscription's status changed (approved, rejected, conflict, …).
 */
class MissionSubscriptionStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public MissionSubscription $missionSubscription,
    ) {}
}
