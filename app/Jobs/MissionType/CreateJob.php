<?php

namespace App\Jobs\MissionType;

use App\Models\MissionType;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): MissionType
    {
        return MissionType::create($this->data);
    }
}
