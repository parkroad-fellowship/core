<?php

namespace App\Jobs\MissionFaqCategory;

use App\Models\MissionFaqCategory;
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

    public function handle(): MissionFaqCategory
    {
        $missionFaqCategory = MissionFaqCategory::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $missionFaqCategory->update($attributes);

        return $missionFaqCategory;
    }
}
