<?php

namespace App\Jobs\MissionGroundSuggestion;

use App\Enums\PRFResponsibleDesk;
use App\Helpers\Utils;
use App\Models\MissionGroundSuggestion;
use App\Notifications\MissionGroundSuggestion\MissionGroundSuggestionReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Notification;

#[Queue('high')]
#[Tries(3)]
class NotifyMissionDeskJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public MissionGroundSuggestion $missionGroundSuggestion,
    ) {}

    public function handle(): void
    {
        $missionGroundSuggestion = $this->missionGroundSuggestion;

        Notification::send(
            Utils::deskRecipients(PRFResponsibleDesk::MISSIONS_DESK),
            new MissionGroundSuggestionReceivedNotification($missionGroundSuggestion),
        );
    }
}
