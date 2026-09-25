<?php

namespace App\Jobs\Church;

use App\Models\Church;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): Church
    {
        return Church::create($this->data);
    }
}
