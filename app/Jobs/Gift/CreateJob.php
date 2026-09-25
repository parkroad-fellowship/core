<?php

namespace App\Jobs\Gift;

use App\Models\Gift;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): Gift
    {
        return Gift::create($this->data);
    }
}
