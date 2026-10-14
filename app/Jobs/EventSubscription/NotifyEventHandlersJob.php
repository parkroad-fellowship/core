<?php

namespace App\Jobs\EventSubscription;

use App\Models\EventSubscription;
use App\Models\Member;
use App\Models\PRFEventHandler;
use App\Notifications\EventSubscription\EventSubscriptionCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Notification;

#[Queue('high')]
#[Tries(3)]
class NotifyEventHandlersJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $eventSubscriptionId,
    ) {}

    public function handle(): void
    {
        $eventSubscription = EventSubscription::findOrFail($this->eventSubscriptionId);
        Member::query()
            ->whereIn(
                'id',
                PRFEventHandler::query()->where('prf_event_id', $eventSubscription->prf_event_id)->select('member_id'),
            )
            ->chunk(30, function ($members) use ($eventSubscription) {
                Notification::send($members, new EventSubscriptionCreatedNotification($eventSubscription));
            });
    }
}
