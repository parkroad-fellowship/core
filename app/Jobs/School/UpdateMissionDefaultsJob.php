<?php

namespace App\Jobs\School;

use App\Models\MissionType;
use App\Models\School;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class UpdateMissionDefaultsJob
{
    use Dispatchable;

    public function __construct(
        public string $schoolUlid,
        public array $data,
    ) {}

    public function handle(): School
    {
        $school = School::query()->where('ulid', $this->schoolUlid)->firstOrFail();

        $entries = collect(Arr::get($this->data, 'types', []))->map(function (array $type) {
            $missionType = MissionType::query()->where('ulid', $type['mission_type_ulid'])->firstOrFail();

            return [
                'mission_type_id' => $missionType->id,
                'start_time' => $type['start_time'] ?? null,
                'end_time' => $type['end_time'] ?? null,
                'capacity' => $type['capacity'] ?? null,
            ];
        })->values()->all();

        $defaultMissionTypeId = isset($this->data['default_mission_type_ulid'])
            ? MissionType::query()->where('ulid', $this->data['default_mission_type_ulid'])->value('id')
            : null;

        $school->setMissionTypeDefaults($entries, defaultMissionTypeId: $defaultMissionTypeId);

        return $school->fresh();
    }
}
