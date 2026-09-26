<?php

namespace App\Jobs\SchoolTerm;

use App\Models\SchoolTerm;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): SchoolTerm
    {
        return SchoolTerm::create($this->data);
    }
}
