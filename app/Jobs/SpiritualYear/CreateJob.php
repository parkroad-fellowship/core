<?php

namespace App\Jobs\SpiritualYear;

use App\Models\SpiritualYear;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): SpiritualYear
    {
        return SpiritualYear::create($this->data);
    }
}
