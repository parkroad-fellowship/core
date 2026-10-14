<?php

namespace App\Jobs\MissionOfflineMember;

use App\Models\MissionOfflineMember;
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

    public function handle(): MissionOfflineMember
    {
        $missionOfflineMember = MissionOfflineMember::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $missionOfflineMember->update($attributes);

        return $missionOfflineMember;
    }
}
