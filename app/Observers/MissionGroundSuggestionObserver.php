<?php

namespace App\Observers;

use App\Events\MissionGroundSuggestion\MissionGroundSuggestionCreated;
use App\Models\MissionGroundSuggestion;

/**
 * Translates MissionGroundSuggestion lifecycle changes into domain events; side effects live in listeners.
 */
class MissionGroundSuggestionObserver
{
    public function created(MissionGroundSuggestion $missionGroundSuggestion): void
    {
        MissionGroundSuggestionCreated::dispatch($missionGroundSuggestion);
    }
}
