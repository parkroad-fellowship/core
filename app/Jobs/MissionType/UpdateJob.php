<?php

namespace App\Jobs\MissionType;

use App\Models\MissionType;
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

    public function handle(): MissionType
    {
        $missionType = MissionType::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $missionType->update($attributes);

        return $missionType;
    }
}
