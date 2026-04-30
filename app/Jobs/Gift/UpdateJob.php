<?php

namespace App\Jobs\Gift;

use App\Models\Gift;
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
        Gift::query()
            ->where('ulid', $this->ulid)
            ->update($this->data);
    }
}
