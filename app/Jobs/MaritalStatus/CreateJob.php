<?php

namespace App\Jobs\MaritalStatus;

use App\Models\MaritalStatus;
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
    public function handle(): MaritalStatus
    {
        return MaritalStatus::create($this->data);
    }
}
