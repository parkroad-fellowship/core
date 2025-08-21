<?php

namespace App\Jobs\PRFEvent;

use App\Models\PRFEvent;
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
    public function handle(): PRFEvent
    {
        return PRFEvent::create($this->data);
    }
}
