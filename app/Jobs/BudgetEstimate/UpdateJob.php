<?php

namespace App\Jobs\BudgetEstimate;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\BudgetEstimate;
use App\Models\MissionType;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;
    use ResolvesULIDs;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): BudgetEstimate
    {
        $budgetEstimate = BudgetEstimate::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'mission_type_ulid' => MissionType::class,
        ]);

        $budgetEstimate->update($attributes);

        return $budgetEstimate;
    }
}
