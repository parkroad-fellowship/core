<?php

namespace App\Jobs\MissionGroundSuggestion;

use App\Models\Member;
use App\Models\MissionGroundSuggestion;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
        public string $missionGroundSuggestionUlid,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->data;
        $missionGroundSuggestionUlid = $this->missionGroundSuggestionUlid;

        $member = Member::query()
            ->where('ulid', $data['suggestor_ulid'])
            ->firstOrFail();

        MissionGroundSuggestion::query()
            ->where('ulid', $missionGroundSuggestionUlid)
            ->update([
                'suggestor_id' => $member->id,
                'name' => $data['name'],
                'contact_person' => $data['contact_person'],
                'contact_number' => $data['contact_number'],
                'status' => $data['status'],
                'notes' => $data['notes'],
            ]);
    }
}
