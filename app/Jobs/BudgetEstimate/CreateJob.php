<?php

namespace App\Jobs\BudgetEstimate;

use App\Models\BudgetEstimate;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): BudgetEstimate
    {
        $morphType = $this->data['budget_estimatable_type'];
        $modelClass = Relation::getMorphedModel($morphType) ?? $morphType;

        $parent = $modelClass::query()
            ->where('ulid', $this->data['budget_estimatable_ulid'])
            ->firstOrFail();

        return BudgetEstimate::create([
            'budget_estimatable_id' => $parent->id,
            'budget_estimatable_type' => $parent->getMorphClass(),
            'grand_total' => $this->data['grand_total'],
            'is_active' => $this->data['is_active'] ?? true,
        ]);
    }
}
