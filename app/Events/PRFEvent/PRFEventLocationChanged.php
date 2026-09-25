<?php

namespace App\Events\PRFEvent;

use App\Models\PRFEvent;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An event's coordinates changed.
 */
class PRFEventLocationChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PRFEvent $prfEvent,
    ) {}
}
