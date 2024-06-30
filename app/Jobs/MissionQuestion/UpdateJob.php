<?php

namespace App\Jobs\MissionQuestion;

use App\Models\Mission;
use App\Models\MissionQuestion;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
        public string $missionQuestionUlid,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $formData = $this->data;
        $missionQuestionUlid = $this->missionQuestionUlid;

        $mission = Mission::query()
            ->where('ulid', $formData['mission_ulid'])
            ->first();

        MissionQuestion::query()
            ->where('ulid', $missionQuestionUlid)
            ->update([
                'mission_id' => $mission->id,
                'question' => $formData['question'],
            ]);
    }
}
