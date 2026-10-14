<?php

namespace App\Jobs\EventSubscription;

use App\Models\EventSubscription;
use Illuminate\Foundation\Bus\Dispatchable;

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

    public function handle(): EventSubscription
    {
        $eventSubscription = EventSubscription::query()->where('ulid', $this->ulid)->firstOrFail();

        // Only the head count can change; the event is fixed once subscribed.
        $attributes = ['number_of_attendees' => $this->data['number_of_attendees']];

        $eventSubscription->update($attributes);

        return $eventSubscription;
    }
}
