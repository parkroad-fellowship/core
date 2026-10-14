<?php

namespace App\Jobs\Profession;

use App\Models\Profession;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): Profession
    {
        return Profession::create($this->data);
    }
}
