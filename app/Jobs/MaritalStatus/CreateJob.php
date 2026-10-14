<?php

namespace App\Jobs\MaritalStatus;

use App\Models\MaritalStatus;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): MaritalStatus
    {
        return MaritalStatus::create($this->data);
    }
}
