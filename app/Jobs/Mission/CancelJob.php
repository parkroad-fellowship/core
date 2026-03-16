<?php

namespace App\Jobs\Mission;

use App\Enums\PRFMissionStatus;
use App\Models\Mission;
use Illuminate\Foundation\Bus\Dispatchable;

class CancelJob
{
    use Dispatchable;

    public function __construct(
        public string $ulid,
        public array $data = [],
    ) {}

    public function handle(): void
    {
        $mission = Mission::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail();

        $update = [
            'status' => PRFMissionStatus::CANCELLED->value,
        ];

        if (isset($this->data['reason'])) {
            $update['executive_summary'] = $this->data['reason'];
        }

        $mission->update($update);
    }
}
