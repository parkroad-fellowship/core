<?php

namespace App\Jobs\MissionGroundSuggestion;

use App\Models\Member;
use App\Models\MissionGroundSuggestion;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): MissionGroundSuggestion
    {
        $data = $this->data;

        $member = Member::query()
            ->where('ulid', $data['suggestor_ulid'])
            ->firstOrFail();

        return MissionGroundSuggestion::create(
            [
                'suggestor_id' => $member->id,
                'name' => $data['name'],
                'contact_person' => $data['contact_person'],
                'contact_number' => $data['contact_number'],
            ],
        );
    }
}
