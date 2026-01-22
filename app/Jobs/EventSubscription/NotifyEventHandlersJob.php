<?php

namespace App\Jobs\EventSubscription;

use App\Models\EventSubscription;
use App\Models\Member;
use App\Models\PRFEventHandler;
use App\Notifications\EventSubscription\NewEventSubscriptionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyEventHandlersJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $eventSubscriptionId,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $eventSubscription = EventSubscription::findOrFail($this->eventSubscriptionId);
        Member::query()
            ->whereIn(
                'id',
                PRFEventHandler::query()
                    ->where('prf_event_id', $eventSubscription->prf_event_id)
                    ->select('member_id')
            )
            ->chunk(30, function ($members) use ($eventSubscription) {
                Notification::send(
                    $members,
                    new NewEventSubscriptionNotification($eventSubscription)
                );
            });
    }
}
