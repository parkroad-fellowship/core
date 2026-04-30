<?php

namespace App\Jobs\MissionSubscription;

use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\MissionSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MarkConflictsJob implements ShouldQueue
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
        $missionSubscription->load(['mission']);

        $mission = $missionSubscription->mission;

        if (! in_array($mission->status, PRFMissionStatus::subscribable())) {
            return;
        }

        // Mark all pending subscriptions for the same member on conflicting missions
        MissionSubscription::query()
            ->where([
                ['id', '!=', $missionSubscription->id],
                'member_id' => $missionSubscription->member_id,
                'status' => PRFMissionSubscriptionStatus::PENDING->value,
            ])
            ->whereHas('mission', fn ($query) => $query->conflictingWith($mission))
            ->update(['status' => PRFMissionSubscriptionStatus::CONFLICT->value]);
    }
}
