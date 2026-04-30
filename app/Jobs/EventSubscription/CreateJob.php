<?php

namespace App\Jobs\EventSubscription;

use App\Models\EventSubscription;
use App\Models\Member;
use App\Models\PRFEvent;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): EventSubscription
    {
        $data = $this->data;

        $prfEvent = PRFEvent::query()
            ->where('ulid', $data['event_ulid'])
            ->first();

        $member = Member::query()
            ->where('ulid', $data['member_ulid'])
            ->first();

        // If an event subscription is soft deleted, restore it
        $eventSubscription = EventSubscription::query()
            ->where([
                'prf_event_id' => $prfEvent->id,
                'member_id' => $member->id,
            ])
            ->withTrashed()
            ->first();

        if ($eventSubscription) {
            $eventSubscription->restore();
            $eventSubscription->refresh();

            return $eventSubscription;
        }

        // Otherwise, make a new entry
        return EventSubscription::create(
            [
                'prf_event_id' => $prfEvent->id,
                'member_id' => $member->id,
                'number_of_attendees' => $data['number_of_attendees'],
            ],
        );
    }
}
