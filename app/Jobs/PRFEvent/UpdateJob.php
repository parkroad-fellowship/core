<?php

namespace App\Jobs\PRFEvent;

use App\Models\PRFEvent;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $ulid,
        public array $data,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        PRFEvent::query()
            ->where('ulid', $this->ulid)
            ->update($this->data);
    }
}
