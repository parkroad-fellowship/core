<?php

namespace App\Jobs\Mission;

use App\Enums\PRFMissionStatus;
use App\Models\Mission;
use Illuminate\Foundation\Bus\Dispatchable;

class ApproveJob
{
    use Dispatchable;

    public function __construct(
        public string $ulid,
    ) {}

    public function handle(): void
    {
        $mission = Mission::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail();

        $mission->update([
            'status' => PRFMissionStatus::APPROVED->value,
        ]);
    }
}
