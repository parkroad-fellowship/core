<?php

namespace App\Jobs\Group;

use App\Models\Group;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): Group
    {
        return Group::create($this->data);
    }
}
