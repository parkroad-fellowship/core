<?php

namespace App\Jobs\MissionSession;

use App\Models\ClassGroup;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionSession;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->data;

        $mission = Mission::where('ulid', $data['mission_ulid'])->firstOrFail();
        $facilitator = Member::where('ulid', $data['facilitator_ulid'])->firstOrFail();
        $speaker = Member::where('ulid', $data['speaker_ulid'])->first();
        $classGroup = ClassGroup::where('ulid', $data['class_group_ulid'])->first();

        MissionSession::query()
            ->where('ulid', $this->ulid)
            ->update([
                'mission_id' => $mission->id,
                'facilitator_id' => $facilitator->id,
                'speaker_id' => $speaker?->id,
                'class_group_id' => $classGroup?->id,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'notes' => $data['notes'],
                'order' => $data['order'],
            ]);
    }
}
