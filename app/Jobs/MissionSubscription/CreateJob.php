<?php

namespace App\Jobs\MissionSubscription;

use App\Enums\PRFMissionRole;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionSubscription;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): MissionSubscription
    {
        $data = $this->data;

        $mission = Mission::query()
            ->where('ulid', $data['mission_ulid'])
            ->first();

        $member = Member::query()
            ->where('ulid', $data['member_ulid'])
            ->first();

        // If a mission subscription is soft deleted, restore it
        $missionSubscription = MissionSubscription::query()
            ->where('mission_id', $mission->id)
            ->where('member_id', $member->id)
            ->withTrashed()
            ->first();

        $notes = Arr::get($data, 'notes');

        if ($missionSubscription) {
            $missionSubscription->restore();
            $missionSubscription->update(
                [
                    'status' => PRFMissionSubscriptionStatus::PENDING,
                    'mission_role' => PRFMissionRole::MEMBER,
                    'notes' => $notes,
                ],
            );
            $missionSubscription->refresh();

            return $missionSubscription;
        }

        // Otherwise, make a new entry
        return MissionSubscription::create(
            [
                'notes' => $notes,
                'status' => PRFMissionSubscriptionStatus::PENDING,
                'mission_id' => $mission->id,
                'member_id' => $member->id,
            ],
        );
    }
}
