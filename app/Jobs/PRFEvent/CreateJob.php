<?php

namespace App\Jobs\PRFEvent;

use App\Models\PRFEvent;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): PRFEvent
    {
        $data = $this->data;

        $event = PRFEvent::create($data);

        if (Arr::has($data, 'participant_member_ulids')) {
            $participantMemberUlids = Arr::get($data, 'participant_member_ulids', []);
            $participants = [];
            foreach ($participantMemberUlids as $memberUlid) {
                $member = \App\Models\Member::query()
                    ->where('ulid', $memberUlid)
                    ->first();

                if ($member) {
                    $participants[] = new \App\Models\PRFEventParticipant([
                        'prf_event_id' => $event->id,
                        'member_id' => $member->id,
                    ]);
                }
            }

            $event->participants()->saveMany($participants);
        }

        return $event;
    }
}
