<?php

namespace App\Jobs\Mission;

use App\Models\Member;
use App\Models\Mission;
use App\Notifications\Mission\NewMissionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyMembersJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Mission $mission,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mission = $this->mission;
        $mission->load(['school', 'missionType']);

        Member::query()
            ->chunk(30, function ($members) use ($mission) {
                Notification::send(
                    $members,
                    new NewMissionNotification($mission),
                );
            });
    }
}
