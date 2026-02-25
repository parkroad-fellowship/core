<?php

namespace App\Jobs\Mission;

use App\Models\Mission;
use App\Models\MissionType;
use App\Models\School;
use App\Models\SchoolTerm;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class UpdateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
        public string $missionUlid,
    ) {}

    public function handle(): void
    {
        $data = $this->data;

        if (isset($data['school_term_ulid'])) {
            $schoolTerm = SchoolTerm::where('ulid', $data['school_term_ulid'])->firstOrFail();
            $data['school_term_id'] = $schoolTerm->id;
        }
        Arr::forget($data, ['school_term_ulid']);

        if (isset($data['mission_type_ulid'])) {
            $missionType = MissionType::where('ulid', $data['mission_type_ulid'])->firstOrFail();
            $data['mission_type_id'] = $missionType->id;
        }
        Arr::forget($data, ['mission_type_ulid']);

        if (isset($data['school_ulid'])) {
            $school = School::where('ulid', $data['school_ulid'])->firstOrFail();
            $data['school_id'] = $school->id;
        }
        Arr::forget($data, ['school_ulid']);

        Mission::query()
            ->where('ulid', $this->missionUlid)
            ->update($data);
    }
}
