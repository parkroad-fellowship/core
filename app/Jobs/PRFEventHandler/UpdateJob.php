<?php

namespace App\Jobs\PRFEventHandler;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\Member;
use App\Models\PRFEvent;
use App\Models\PRFEventHandler;
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

    public function handle(): PRFEventHandler
    {
        $prfEventHandler = PRFEventHandler::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'prf_event_ulid' => PRFEvent::class,
            'member_ulid' => Member::class,
        ]);

        $prfEventHandler->update($attributes);

        return $prfEventHandler;
    }
}
