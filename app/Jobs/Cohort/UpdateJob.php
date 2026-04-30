<?php

namespace App\Jobs\Cohort;

use App\Models\Cohort;
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
        Cohort::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail()
            ->update($this->data);
    }
}
