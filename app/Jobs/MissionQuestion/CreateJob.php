<?php

namespace App\Jobs\MissionQuestion;

use App\Models\Mission;
use App\Models\MissionQuestion;
use Illuminate\Foundation\Bus\Dispatchable;

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
    public function handle(): MissionQuestion
    {
        $data = $this->data;

        $mission = Mission::query()
            ->where('ulid', $data['mission_ulid'])
            ->first();

        return MissionQuestion::create(
            [
                'mission_id' => $mission->id,
                'question' => $data['question'],
            ],
        );
    }
}
