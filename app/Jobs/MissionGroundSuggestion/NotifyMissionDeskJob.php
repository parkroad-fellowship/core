<?php

namespace App\Jobs\MissionGroundSuggestion;

use App\Models\Member;
use App\Models\MissionGroundSuggestion;
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
            Member::whereIn('email', config('prf.app.missions_desk.emails'))->get(),
            new \App\Notifications\MissionGroundSuggestion\NotifyMissionDeskNotification($missionGroundSuggestion),
        );
    }
}
