<?php

namespace App\Jobs\MissionGroundSuggestion;

use App\Models\AppSetting;
use App\Models\Member;
use App\Models\MissionGroundSuggestion;
use App\Notifications\MissionGroundSuggestion\NotifyMissionDeskNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyMissionDeskJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MissionGroundSuggestion $missionGroundSuggestion,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $missionGroundSuggestion = $this->missionGroundSuggestion;

        Notification::send(
            Member::whereIn('email', AppSetting::get('desk_emails.missions', []))->get(),
            new NotifyMissionDeskNotification($missionGroundSuggestion),
        );
    }
}
