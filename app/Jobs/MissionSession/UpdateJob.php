<?php

namespace App\Jobs\MissionSession;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\ClassGroup;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionSession;
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

    public function handle(): MissionSession
    {
        $missionSession = MissionSession::query()->where('ulid', $this->ulid)->firstOrFail();

        $resolved = $this->resolveULIDs($this->data, [
            'mission_ulid' => Mission::class,
            'facilitator_ulid' => [Member::class, 'facilitator_id'],
            'speaker_ulid' => [Member::class, 'speaker_id'],
            'class_group_ulid' => ClassGroup::class,
        ]);

        // A full update: optional relations and notes left out of the request are cleared.
        $attributes = [
            'mission_id' => $resolved['mission_id'],
            'facilitator_id' => $resolved['facilitator_id'],
            'speaker_id' => $resolved['speaker_id'] ?? null,
            'class_group_id' => $resolved['class_group_id'] ?? null,
            'starts_at' => $resolved['starts_at'],
            'ends_at' => $resolved['ends_at'],
            'notes' => $resolved['notes'] ?? null,
            'order' => $resolved['order'] ?? 0,
        ];

        $missionSession->update($attributes);

        return $missionSession;
    }
}
