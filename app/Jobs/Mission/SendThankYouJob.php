<?php

namespace App\Jobs\Mission;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionSubscription;
use App\Notifications\Mission\ThankYouNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class SendThankYouJob implements ShouldQueue
{
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
        Member::query()
            ->whereIn('id', MissionSubscription::query()
                ->select('member_id')
                ->where([
                    'mission_id' => $this->mission->id,
                    'status' => PRFMissionSubscriptionStatus::APPROVED,
                ]))
            ->chunk(30, function ($members) {
                Notification::send(
                    $members,
                    new ThankYouNotification(
                        mission: $this->mission
                    ),
                );
            });
    }
}
