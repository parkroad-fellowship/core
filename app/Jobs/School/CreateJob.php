<?php

namespace App\Jobs\School;

use App\Models\School;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): School
    {
        $data = $this->data;

        return School::create($data);
    }
}
