<?php

namespace App\Jobs\PRFEvent;

use App\Models\Member;
use App\Models\PRFEvent;
use App\Notifications\PRFEvent\NewEventNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyMembersJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PRFEvent $prfEvent,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $prfEvent = $this->prfEvent;

        Member::query()
            ->where('is_desk_email', false)
            ->chunk(30, function ($members) use ($prfEvent) {
                Notification::send(
                    $members,
                    new NewEventNotification($prfEvent),
                );
            });
    }
}
