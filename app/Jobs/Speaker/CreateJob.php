<?php

namespace App\Jobs\Speaker;

use App\Models\Speaker;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): Speaker
    {
        return Speaker::create($this->data);
    }
}
