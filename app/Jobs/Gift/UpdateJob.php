<?php

namespace App\Jobs\Gift;

use App\Models\Gift;
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

    public function handle(): Gift
    {
        $gift = Gift::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $gift->update($attributes);

        return $gift;
    }
}
