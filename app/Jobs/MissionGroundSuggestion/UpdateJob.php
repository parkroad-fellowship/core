<?php

namespace App\Jobs\MissionGroundSuggestion;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\Member;
use App\Models\MissionGroundSuggestion;
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

    public function handle(): MissionGroundSuggestion
    {
        $missionGroundSuggestion = MissionGroundSuggestion::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'suggestor_ulid' => [Member::class, 'suggestor_id'],
        ]);

        $missionGroundSuggestion->update($attributes);

        return $missionGroundSuggestion;
    }
}
