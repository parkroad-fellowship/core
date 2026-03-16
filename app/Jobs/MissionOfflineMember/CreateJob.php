<?php

namespace App\Jobs\MissionOfflineMember;

use App\Models\Mission;
use App\Models\MissionOfflineMember;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): MissionOfflineMember
    {
        $data = $this->data;

        $mission = Mission::where('ulid', $data['mission_ulid'])->firstOrFail();
        $data['mission_id'] = $mission->id;
        Arr::forget($data, ['mission_ulid']);

        return MissionOfflineMember::create($data);
    }
}
