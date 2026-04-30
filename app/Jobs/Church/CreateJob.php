<?php

namespace App\Jobs\Church;

use App\Models\Church;
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
    public function handle(): Church
    {
        return Church::create($this->data);
    }
}
