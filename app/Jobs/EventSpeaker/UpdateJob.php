<?php

namespace App\Jobs\EventSpeaker;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\EventSpeaker;
use App\Models\PRFEvent;
use App\Models\Speaker;
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

    public function handle(): EventSpeaker
    {
        $eventSpeaker = EventSpeaker::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'prf_event_ulid' => PRFEvent::class,
            'speaker_ulid' => Speaker::class,
        ]);

        $eventSpeaker->update($attributes);

        return $eventSpeaker;
    }
}
