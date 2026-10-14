<?php

namespace App\Jobs\DebriefNote;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\DebriefNote;
use App\Models\Mission;
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

    public function handle(): DebriefNote
    {
        $debriefNote = DebriefNote::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'mission_ulid' => Mission::class,
        ]);

        $debriefNote->update($attributes);

        return $debriefNote;
    }
}
