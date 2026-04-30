<?php

namespace App\Jobs\PRFEventParticipant;

use App\Models\Member;
use App\Models\PRFEvent;
use App\Models\PRFEventParticipant;
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

        if (isset($update['member_ulid'])) {
            $member = Member::query()->where('ulid', $update['member_ulid'])->firstOrFail();
            $update['member_id'] = $member->id;
            unset($update['member_ulid']);
        }

        PRFEventParticipant::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail()
            ->update($update);
    }
}
