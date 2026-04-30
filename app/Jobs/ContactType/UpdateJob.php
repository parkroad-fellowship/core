<?php

namespace App\Jobs\ContactType;

use App\Models\ContactType;
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

        return ContactType::query()
            ->where('ulid', $this->ulid)
            ->update($data);
    }
}
