<?php

namespace App\Jobs\PRFEvent;

use App\Models\Member;
use App\Models\PRFEvent;
use App\Models\PRFEventParticipant;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class UpdateJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): PRFEvent
    {
        return DB::transaction(function (): PRFEvent {
            $prfEvent = PRFEvent::query()->where('ulid', $this->ulid)->firstOrFail();

            $attributes = $this->data;
            unset($attributes['participant_member_ulids']);

            $prfEvent->update($attributes);

            if (array_key_exists('participant_member_ulids', $this->data)) {
                $memberUlids = $this->data['participant_member_ulids'];

                $this->syncParticipants(
                    $prfEvent,
                    is_array($memberUlids) ? array_filter($memberUlids, is_string(...)) : [],
                );
            }

            return $prfEvent;
        });
    }

    /**
     * @param  array<array-key, string>  $memberUlids
     */
    private function syncParticipants(PRFEvent $prfEvent, array $memberUlids): void
    {
        $prfEvent
            ->participants()
            ->get()
            ->each(fn(PRFEventParticipant $participant) => $participant->delete());

        Member::query()
            ->whereIn('ulid', $memberUlids)
            ->get()
            ->each(fn(Member $member) => $prfEvent->participants()->create(['member_id' => $member->id]));
    }
}
