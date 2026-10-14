<?php

namespace App\Jobs\Mission;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionSubscription;
use App\Notifications\Mission\MissionServicedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Notification;

#[Queue('high')]
#[Tries(3)]
class SendThankYouJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Mission $mission,
    ) {}

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
                Notification::send($members, new MissionServicedNotification(mission: $this->mission));
            });
    }
}
