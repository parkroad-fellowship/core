<?php

namespace App\Http\Resources\School;

use App\Http\Resources\MissionType\Resource as MissionTypeResource;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class MissionDefaultsResource extends JsonResource
{
    public function __construct(
        School $resource,
        /** @var Collection<int, \App\Models\MissionType> */
        public Collection $missionTypes,
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $types = collect($this->mission_defaults['types'] ?? [])
            ->map(fn(array $defaults, string $missionTypeId) => [
                'mission_type' => new MissionTypeResource($this->missionTypes->get((int) $missionTypeId)),
                'start_time' => $defaults['start_time'] ?? null,
                'end_time' => $defaults['end_time'] ?? null,
                'capacity' => isset($defaults['capacity']) ? (int) $defaults['capacity'] : null,
            ])
            ->filter(fn(array $type) => $type['mission_type']->resource !== null)
            ->values();

        return [
            'entity' => 'school_mission_defaults',

            'school_ulid' => $this->ulid,

            'default_mission_type' => new MissionTypeResource($this->missionTypes->get(
                $this->mission_defaults['default_mission_type_id'] ?? null,
            )),

            'types' => $types,
        ];
    }
}
