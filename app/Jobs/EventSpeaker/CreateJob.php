<?php

namespace App\Jobs\EventSpeaker;

use App\Models\EventSpeaker;
use App\Models\PRFEvent;
use App\Models\Speaker;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): EventSpeaker
    {
        $prfEvent = PRFEvent::query()->where('ulid', $this->data['prf_event_ulid'])->firstOrFail();
        $speaker = Speaker::query()->where('ulid', $this->data['speaker_ulid'])->firstOrFail();

        return EventSpeaker::create([
            'prf_event_id' => $prfEvent->id,
            'speaker_id' => $speaker->id,
            'topic' => $this->data['topic'] ?? null,
            'description' => $this->data['description'] ?? null,
            'comments' => $this->data['comments'] ?? null,
        ]);
    }
}
