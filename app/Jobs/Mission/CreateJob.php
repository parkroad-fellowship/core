<?php

namespace App\Jobs\Mission;

use App\Models\Mission;
use App\Models\MissionType;
use App\Models\School;
use App\Models\SchoolTerm;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): Mission
    {
        $data = $this->data;

        $schoolTerm = SchoolTerm::where('ulid', $data['school_term_ulid'])->firstOrFail();
        $data['school_term_id'] = $schoolTerm->id;
        Arr::forget($data, ['school_term_ulid']);

        $missionType = MissionType::where('ulid', $data['mission_type_ulid'])->firstOrFail();
        $data['mission_type_id'] = $missionType->id;
        Arr::forget($data, ['mission_type_ulid']);

        $school = School::where('ulid', $data['school_ulid'])->firstOrFail();
        $data['school_id'] = $school->id;
        Arr::forget($data, ['school_ulid']);

        return Mission::create($data);
    }
}
