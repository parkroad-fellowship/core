<?php

namespace App\Jobs\Mission;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\Mission;
use App\Models\MissionType;
use App\Models\School;
use App\Models\SchoolTerm;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;
    use ResolvesULIDs;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): Mission
    {
        $mission = Mission::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'school_term_ulid' => SchoolTerm::class,
            'mission_type_ulid' => MissionType::class,
            'school_ulid' => School::class,
        ]);

        $mission->update($attributes);

        return $mission;
    }
}
