<?php

namespace App\Jobs\Church;

use App\Models\Church;
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

    public function handle(): Church
    {
        $church = Church::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $church->update($attributes);

        return $church;
    }
}
