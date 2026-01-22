<?php

namespace App\Services;

use App\Models\School;

class MissionDefaultsService
{
    /**
     * Get mission defaults for a school.
     *
     * @return array{
     *     start_time: string|null,
     *     end_time: string|null,
     *     capacity: int|null,
     *     mission_type_id: int|null,
     *     source: string,
     *     source_label: string
     * }
     */
    public function getDefaultsForSchool(int|string $schoolId): array
    {
        $school = School::find($schoolId);

        if (! $school) {
            return $this->emptyDefaults();
        }

        $defaults = $school->getMissionDefaults();

        return [
            'start_time' => $defaults['default_start_time'],
            'end_time' => $defaults['default_end_time'],
            'capacity' => $defaults['default_capacity'],
            'mission_type_id' => $defaults['default_mission_type_id'],
            'source' => $defaults['source'],
            'source_label' => $this->getSourceLabel($defaults['source']),
        ];
    }

    /**
     * Get empty defaults when no school is selected.
     *
     * @return array{
     *     start_time: null,
     *     end_time: null,
     *     capacity: null,
     *     mission_type_id: null,
     *     source: string,
     *     source_label: string
     * }
     */
    protected function emptyDefaults(): array
    {
        return [
            'start_time' => null,
            'end_time' => null,
            'capacity' => null,
            'mission_type_id' => null,
            'source' => 'none',
            'source_label' => '',
        ];
    }

    /**
     * Get a human-readable label for the source of defaults.
     */
    protected function getSourceLabel(string $source): string
    {
        return match ($source) {
            'school_defaults' => 'Fields auto-filled from school defaults',
            'recent_mission' => 'Fields auto-filled from previous mission',
            default => '',
        };
    }
}
