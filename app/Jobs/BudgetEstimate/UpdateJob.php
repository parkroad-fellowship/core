<?php

namespace App\Jobs\BudgetEstimate;

use App\Models\BudgetEstimate;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): void
    {
        BudgetEstimate::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail()
            ->update($this->data);
    }
}
