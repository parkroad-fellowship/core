<?php

namespace App\Jobs\PRFEvent;

use App\Models\AppSetting;
use App\Models\Member;
use App\Models\PRFEvent;
use App\Notifications\PRFEvent\PRFEventAnnouncedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Notification;

#[Queue('high')]
#[Tries(3)]
class NotifyMembersJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PRFEvent $prfEvent,
    ) {}

    public function handle(): void
    {
        $prfEvent = $this->prfEvent;

        $excludeEmails = AppSetting::query()->where('key', 'organization.excluded_emails')->value('value');

        Member::query()
            ->whereNotIn('email', json_decode($excludeEmails))
            ->chunk(30, function ($members) use ($prfEvent) {
                Notification::send($members, new PRFEventAnnouncedNotification($prfEvent));
            });
    }
}
