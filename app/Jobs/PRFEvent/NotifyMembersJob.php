<?php

namespace App\Jobs\PRFEvent;

use App\Models\AppSetting;
use App\Models\Member;
use App\Models\PRFEvent;
use App\Notifications\PRFEvent\NewEventNotification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyMembersJob implements ShouldBeUnique, ShouldQueue
{
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

        $excludeEmails = AppSetting::query()
            ->where('key', 'organization.excluded_emails')
            ->value('value');

        Member::query()
            ->whereNotIn('email', json_decode($excludeEmails))
            ->chunk(30, function ($members) use ($prfEvent) {
                Notification::send(
                    $members,
                    new NewEventNotification($prfEvent),
                );
            });
    }
}
