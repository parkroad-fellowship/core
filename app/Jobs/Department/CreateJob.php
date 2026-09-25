<?php

namespace App\Jobs\Department;

use App\Models\Department;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): Department
    {
        return Department::create($this->data);
    }
}
