<?php

namespace App\Jobs\Module;

use App\Models\Module;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * @param  array<mixed>  $data  form or request input; only named fields are used
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): Module
    {
        $module = Module::query()->withTrashed()->where('ulid', $this->ulid)->firstOrFail();
        $module->update(array_filter($this->data, is_string(...), ARRAY_FILTER_USE_KEY));

        return $module;
    }
}
