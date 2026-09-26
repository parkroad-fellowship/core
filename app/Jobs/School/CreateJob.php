<?php

namespace App\Jobs\School;

use App\Models\School;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
    ) {}

    public function handle(): School
    {
        return School::create($this->data);
    }
}
