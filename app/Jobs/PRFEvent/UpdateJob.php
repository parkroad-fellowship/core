<?php

namespace App\Jobs\PRFEvent;

use App\Models\Member;
use App\Models\PRFEvent;
use App\Models\PRFEventParticipant;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class UpdateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $ulid,
        public array $data,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->data;

        PRFEvent::query()
            ->where('ulid', $this->ulid)
            ->update($data);

        if (Arr::has($data, 'participant_member_ulids')) {
            $prfEvent = PRFEvent::query()
                ->where('ulid', $this->ulid)
                ->first();

            $prfEvent->participants()->delete();

            $participantMemberUlids = Arr::get($data, 'participant_member_ulids', []);
            $participants = [];
            foreach ($participantMemberUlids as $memberUlid) {
                $member = Member::query()
                    ->where('ulid', $memberUlid)
                    ->first();
                if ($member) {
                    $participants[] = new PRFEventParticipant([
                        'prf_event_id' => $prfEvent->id,
                        'member_id' => $member->id,
                    ]);
                }
            }

            $prfEvent->participants()->saveMany($participants);
        }
    }
}
