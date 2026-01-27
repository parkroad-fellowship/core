<?php

namespace App\Jobs\Department;

use App\Models\Department;
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
    public function handle(): Department
    {
        return Department::create($this->data);
    }
}
