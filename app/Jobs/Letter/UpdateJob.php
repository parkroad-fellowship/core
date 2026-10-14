<?php

namespace App\Jobs\Letter;

use App\Models\Letter;
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

    public function handle(): Letter
    {
        $letter = Letter::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $letter->update($attributes);

        return $letter;
    }
}
