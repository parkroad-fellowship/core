<?php

namespace App\Jobs\SpiritualYear;

use App\Models\SpiritualYear;
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

    public function handle(): SpiritualYear
    {
        $spiritualYear = SpiritualYear::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $spiritualYear->update($attributes);

        return $spiritualYear;
    }
}
