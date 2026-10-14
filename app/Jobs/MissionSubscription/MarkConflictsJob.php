<?php

namespace App\Jobs\MissionSubscription;

use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Mission;
use App\Models\MissionSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;

#[Queue('high')]
#[Tries(3)]
class MarkConflictsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public MissionSubscription $missionSubscription,
    ) {}

    public function handle(): void
    {
        $missionSubscription = $this->missionSubscription;
        $missionSubscription->load(['mission']);

        $mission = $missionSubscription->mission;

        if ($mission === null) {
            return;
        }

        if (!$mission->status->is(...PRFMissionStatus::subscribable())) {
            return;
        }

        // Mark all pending subscriptions for the same member on conflicting missions
        MissionSubscription::query()
            ->where([
                ['id', '!=', $missionSubscription->id],
                'member_id' => $missionSubscription->member_id,
                'status' => PRFMissionSubscriptionStatus::PENDING->value,
            ])
            ->whereIn('mission_id', Mission::query()->conflictingWith($mission)->select('id'))
            ->lazyById()
            ->each(fn(MissionSubscription $conflict) => $conflict->update([
                'status' => PRFMissionSubscriptionStatus::CONFLICT,
            ]));
    }
}
