<?php

namespace App\Events\Mission;

use App\Models\Mission;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A WhatsApp group link was added or changed on a mission.
 */
class MissionWhatsAppGroupLinked implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Mission $mission,
    ) {}
}
