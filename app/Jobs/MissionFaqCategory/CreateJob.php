<?php

namespace App\Jobs\MissionFaqCategory;

use App\Models\MissionFaqCategory;
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
    public function handle(): MissionFaqCategory
    {
        return MissionFaqCategory::create($this->data);
    }
}
