<?php

namespace App\Jobs\PRFEventParticipant;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\Member;
use App\Models\PRFEvent;
use App\Models\PRFEventParticipant;
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

    public function handle(): PRFEventParticipant
    {
        $prfEventParticipant = PRFEventParticipant::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'prf_event_ulid' => PRFEvent::class,
            'member_ulid' => Member::class,
        ]);

        $prfEventParticipant->update($attributes);

        return $prfEventParticipant;
    }
}
