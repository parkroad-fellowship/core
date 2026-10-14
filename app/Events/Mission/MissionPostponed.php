<?php

namespace App\Events\Mission;

use App\Models\Mission;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * A mission was postponed. Carries the dates it had before the change.
 */
class MissionPostponed implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Mission $mission,
        public ?Carbon $originalStartDate = null,
        public ?Carbon $originalEndDate = null,
    ) {}
}
