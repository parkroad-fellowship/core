<?php

namespace App\Jobs\Department;

use App\Models\Department;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Department::query()
            ->where('ulid', $this->ulid)
            ->update($this->data);
    }
}
