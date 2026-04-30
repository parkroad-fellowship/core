<?php

namespace App\Jobs\EventSubscription;

use App\Models\EventSubscription;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
        public string $eventSubscriptionUlid,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->data;
        $eventSubscriptionUlid = $this->eventSubscriptionUlid;

        EventSubscription::query()
            ->where('ulid', $eventSubscriptionUlid)
            ->update([
                'number_of_attendees' => $data['number_of_attendees'],
            ]);
    }
}
