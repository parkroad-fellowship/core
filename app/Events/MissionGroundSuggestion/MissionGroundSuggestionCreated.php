<?php

namespace App\Events\MissionGroundSuggestion;

use App\Models\MissionGroundSuggestion;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A new mission ground was suggested.
 */
class MissionGroundSuggestionCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public MissionGroundSuggestion $missionGroundSuggestion,
    ) {}
}
