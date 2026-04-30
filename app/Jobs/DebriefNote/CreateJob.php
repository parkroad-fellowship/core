<?php

namespace App\Jobs\DebriefNote;

use App\Models\DebriefNote;
use App\Models\Mission;
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
    public function handle(): DebriefNote
    {
        $data = $this->data;

        $mission = Mission::query()
            ->where('ulid', $data['mission_ulid'])
            ->first();

        return DebriefNote::create(
            [
                'mission_id' => $mission->id,
                'note' => $data['note'],
            ],
        );
    }
}
