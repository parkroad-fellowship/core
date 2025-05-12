<?php

namespace App\Jobs\MissionSubscription;

use App\Models\MissionSubscription;
use App\Notifications\MissionSubscription\NotifyMemberOfSubscriptionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyMemberJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MissionSubscription $missionSubscription,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $missionSubscription = $this->missionSubscription;
        $missionSubscription->load(['member']);

        $member = $missionSubscription->member;

        Notification::send(
            $member,
            new NotifyMemberOfSubscriptionNotification($missionSubscription)
        );
    }
}
