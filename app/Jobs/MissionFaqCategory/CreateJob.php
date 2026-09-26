<?php

namespace App\Jobs\MissionFaqCategory;

use App\Models\MissionFaqCategory;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): MissionFaqCategory
    {
        return MissionFaqCategory::create($this->data);
    }
}
