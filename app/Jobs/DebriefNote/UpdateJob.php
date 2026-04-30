<?php

namespace App\Jobs\DebriefNote;

use App\Models\DebriefNote;
use App\Models\Mission;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
        public string $debriefNoteUlid,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $formData = $this->data;
        $debriefNoteUlid = $this->debriefNoteUlid;

        $mission = Mission::query()
            ->where('ulid', $formData['mission_ulid'])
            ->first();

        DebriefNote::query()
            ->where('ulid', $debriefNoteUlid)
            ->update([
                'mission_id' => $mission->id,
                'note' => $formData['note'],
            ]);
    }
}
