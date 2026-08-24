<?php

namespace App\Jobs\BudgetEstimate;

use App\Models\BudgetEstimate;
use App\Models\MissionType;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class UpdateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): void
    {
        $estimate = BudgetEstimate::query()->where('ulid', $this->ulid)->firstOrFail();

        $update = Arr::except($this->data, ['mission_type_ulid']);

        if (isset($this->data['mission_type_ulid'])) {
            $update['mission_type_id'] = MissionType::query()
                ->where('ulid', $this->data['mission_type_ulid'])
                ->value('id');
        }

        $estimate->update($update);
    }
}
