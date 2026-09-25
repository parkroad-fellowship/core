<?php

namespace App\Jobs\Speaker;

use App\Models\Speaker;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): Speaker
    {
        $speaker = Speaker::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $speaker->update($attributes);

        return $speaker;
    }
}
