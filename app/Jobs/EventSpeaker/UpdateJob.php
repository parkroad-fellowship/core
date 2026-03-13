<?php

namespace App\Jobs\EventSpeaker;

use App\Models\EventSpeaker;
use App\Models\PRFEvent;
use App\Models\Speaker;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): void
    {
        $update = $this->data;

        if (isset($update['prf_event_ulid'])) {
            $prfEvent = PRFEvent::query()->where('ulid', $update['prf_event_ulid'])->firstOrFail();
            $update['prf_event_id'] = $prfEvent->id;
            unset($update['prf_event_ulid']);
        }

        if (isset($update['speaker_ulid'])) {
            $speaker = Speaker::query()->where('ulid', $update['speaker_ulid'])->firstOrFail();
            $update['speaker_id'] = $speaker->id;
            unset($update['speaker_ulid']);
        }

        EventSpeaker::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail()
            ->update($update);
    }
}
