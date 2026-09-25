<?php

namespace App\Jobs\School;

use App\Models\School;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): School
    {
        $data = $this->data;

        return School::create($data);
    }
}
