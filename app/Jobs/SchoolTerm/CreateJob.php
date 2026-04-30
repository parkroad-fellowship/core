<?php

namespace App\Jobs\SchoolTerm;

use App\Models\SchoolTerm;
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
    public function handle(): SchoolTerm
    {
        return SchoolTerm::create($this->data);
    }
}
