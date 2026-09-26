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
class IdentifyConflictJob implements ShouldQueue
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

        // Check if the member has any approved subscriptions on conflicting missions
        $hasConflict = MissionSubscription::query()
            ->where([
                ['id', '!=', $missionSubscription->id],
                'member_id' => $missionSubscription->member_id,
                'status' => PRFMissionSubscriptionStatus::APPROVED->value,
            ])
            ->whereIn('mission_id', Mission::query()->conflictingWith($mission)->select('id'))
            ->exists();

        if ($hasConflict) {
            $missionSubscription->update(['status' => PRFMissionSubscriptionStatus::CONFLICT->value]);
        }
    }
}
