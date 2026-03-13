<?php

namespace App\Jobs\Letter;

use App\Models\Letter;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): Letter
    {
        return Letter::create($this->data);
    }
}
