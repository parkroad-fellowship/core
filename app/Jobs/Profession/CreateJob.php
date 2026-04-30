<?php

namespace App\Jobs\Profession;

use App\Models\Profession;
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
    public function handle(): Profession
    {
        return Profession::create($this->data);
    }
}
