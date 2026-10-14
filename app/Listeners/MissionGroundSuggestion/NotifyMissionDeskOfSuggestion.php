<?php

namespace App\Listeners\MissionGroundSuggestion;

use App\Events\MissionGroundSuggestion\MissionGroundSuggestionCreated;
use App\Jobs\MissionGroundSuggestion\NotifyMissionDeskJob;

class NotifyMissionDeskOfSuggestion
{
    public function handle(MissionGroundSuggestionCreated $event): void
    {
        NotifyMissionDeskJob::dispatch($event->missionGroundSuggestion);
    }
}
