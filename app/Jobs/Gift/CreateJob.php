<?php

namespace App\Jobs\Gift;

use App\Models\Gift;
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
    public function handle(): Gift
    {
        return Gift::create($this->data);
    }
}
