<?php

namespace App\Jobs\MissionSubscription;

use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionSubscription;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {
    }

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

        return MissionSubscription::create(
            [
                'mission_id' => $mission->id,
                'member_id' => $member->id,
            ],
        );
    }
}
