<?php

namespace App\Jobs\School;

use App\Models\School;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $ulid,
        public array $data
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): int
    {
        $data = $this->data;

        return School::query()
            ->where('ulid', $this->ulid)
            ->update($data);
    }
}
