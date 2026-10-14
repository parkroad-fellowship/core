<?php

namespace App\Jobs\Module;

use App\Models\Module;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * @param  array<mixed>  $data  form or request input; only named fields are used
     */
    public function __construct(
        public array $data,
    ) {}

    public function handle(): Module
    {
        return Module::query()->create(array_filter($this->data, is_string(...), ARRAY_FILTER_USE_KEY));
    }
}
