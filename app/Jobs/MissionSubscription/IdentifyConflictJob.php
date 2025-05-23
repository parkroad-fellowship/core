<?php

namespace App\Jobs\MissionSubscription;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Mission;
use App\Models\MissionSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IdentifyConflictJob implements ShouldQueue
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

        // Get all approved subscriptions for the member, excluding the current one
        // and missions that overlap with the current mission

        $conflictingSubscriptions = MissionSubscription::query()
            ->where([
                ['id', '!=', $missionSubscription->id],
                'member_id' => $missionSubscription->member_id,
                'status' => PRFMissionSubscriptionStatus::APPROVED,
            ])
            ->whereHas('mission', function ($query) use ($missionSubscription) {
                $query
                    ->where(function ($query) use ($missionSubscription) {
                        $query
                            // Exclude the current mission
                            ->where('missions.id', '!=', $missionSubscription->mission_id)
                            // Check for any overlap between date ranges
                            ->where(function ($q) use ($missionSubscription) {
                                // Mission starts during current mission
                                $q->whereDate('start_date', '>=', $missionSubscription->mission->start_date)
                                    ->whereDate('start_date', '<=', $missionSubscription->mission->end_date);
                            })
                            ->orWhere(function ($q) use ($missionSubscription) {
                                // Mission ends during current mission
                                $q->whereDate('end_date', '>=', $missionSubscription->mission->start_date)
                                    ->whereDate('end_date', '<=', $missionSubscription->mission->end_date);
                            })
                            ->orWhere(function ($q) use ($missionSubscription) {
                                // Mission completely overlaps current mission
                                $q->whereDate('start_date', '<=', $missionSubscription->mission->start_date)
                                    ->whereDate('end_date', '>=', $missionSubscription->mission->end_date);
                            });
                    });
            })
            ->get();

        if ($conflictingSubscriptions->isEmpty()) {
            return;
        }

        // If there are any conflicting subscriptions, mark the current one as conflict
        $missionSubscription->update(['status' => PRFMissionSubscriptionStatus::CONFLICT]);
    }
}
