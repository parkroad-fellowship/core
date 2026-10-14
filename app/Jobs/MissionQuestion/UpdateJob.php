<?php

namespace App\Jobs\MissionQuestion;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\Mission;
use App\Models\MissionQuestion;
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

    public function handle(): MissionQuestion
    {
        $missionQuestion = MissionQuestion::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'mission_ulid' => Mission::class,
        ]);

        $missionQuestion->update($attributes);

        return $missionQuestion;
    }
}
