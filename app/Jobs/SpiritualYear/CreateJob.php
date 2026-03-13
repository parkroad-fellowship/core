<?php

namespace App\Jobs\SpiritualYear;

use App\Models\SpiritualYear;
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
    public function handle(): SpiritualYear
    {
        return SpiritualYear::create($this->data);
    }
}
