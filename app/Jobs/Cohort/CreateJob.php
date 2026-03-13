<?php

namespace App\Jobs\Cohort;

use App\Models\Cohort;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): Cohort
    {
        return Cohort::create($this->data);
    }
}
