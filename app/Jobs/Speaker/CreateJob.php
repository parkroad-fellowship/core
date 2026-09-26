<?php

namespace App\Jobs\Speaker;

use App\Models\Speaker;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): Speaker
    {
        return Speaker::create($this->data);
    }
}
