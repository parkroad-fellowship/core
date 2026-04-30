<?php

namespace App\Jobs\PRFEventParticipant;

use App\Models\Member;
use App\Models\PRFEvent;
use App\Models\PRFEventParticipant;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): PRFEventParticipant
    {
        $prfEvent = PRFEvent::query()->where('ulid', $this->data['prf_event_ulid'])->firstOrFail();
        $member = Member::query()->where('ulid', $this->data['member_ulid'])->firstOrFail();

        return PRFEventParticipant::create([
            'prf_event_id' => $prfEvent->id,
            'member_id' => $member->id,
        ]);
    }
}
