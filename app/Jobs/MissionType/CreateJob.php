<?php

namespace App\Jobs\MissionType;

use App\Models\MissionType;
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
    public function handle(): MissionType
    {
        return MissionType::create($this->data);
    }
}
