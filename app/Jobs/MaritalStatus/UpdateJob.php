<?php

namespace App\Jobs\MaritalStatus;

use App\Models\MaritalStatus;
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
        MaritalStatus::query()
            ->where('ulid', $this->ulid)
            ->update($this->data);
    }
}
