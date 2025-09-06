<?php

namespace App\Jobs\AccountingEvent;

use App\Enums\PRFMorphType;
use App\Models\AccountingEvent;
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
    public function handle(): AccountingEvent
    {
        $data = $this->data;

        $accountingEventable = PRFMorphType::fromValue($data['accounting_eventable_type'])->getModel()::query()
            ->where('ulid', $data['accounting_eventable_ulid'])
            ->first();
        $data['accounting_eventable_id'] = $accountingEventable->id;
        Arr::forget($data, ['accounting_eventable_ulid']);

        $accountingEvent = AccountingEvent::create($data);

        if (Arr::has($data, 'participant_member_ulids')) {
            $participantMemberUlids = Arr::get($data, 'participant_member_ulids', []);
            $participants = [];
            foreach ($participantMemberUlids as $memberUlid) {
                $member = \App\Models\Member::query()
                    ->where('ulid', $memberUlid)
                    ->first();

                if ($member) {
                    $participants[] = new \App\Models\AccountingEventParticipant([
                        'accounting_event_id' => $accountingEvent->id,
                        'member_id' => $member->id,
                    ]);
                }
            }
            $accountingEvent->participants()->saveMany($participants);
        }

        return $accountingEvent;
    }
}
