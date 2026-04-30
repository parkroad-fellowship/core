<?php

namespace App\Observers;

use App\Jobs\MissionGroundSuggestion\NotifyMissionDeskJob;
use App\Models\MissionGroundSuggestion;

class MissionGroundSuggestionObserver
{
    /**
     * Handle the MissionGroundSuggestion "created" event.
     */
    public function created(MissionGroundSuggestion $missionGroundSuggestion): void
    {
        NotifyMissionDeskJob::dispatch($missionGroundSuggestion);
    }

    /**
     * Handle the MissionGroundSuggestion "updated" event.
     */
    public function updated(MissionGroundSuggestion $missionGroundSuggestion): void
    {
        //
    }

    /**
     * Handle the MissionGroundSuggestion "deleted" event.
     */
    public function deleted(MissionGroundSuggestion $missionGroundSuggestion): void
    {
        //
    }

    /**
     * Handle the MissionGroundSuggestion "restored" event.
     */
    public function restored(MissionGroundSuggestion $missionGroundSuggestion): void
    {
        //
    }

    /**
     * Handle the MissionGroundSuggestion "force deleted" event.
     */
    public function forceDeleted(MissionGroundSuggestion $missionGroundSuggestion): void
    {
        //
    }
}
