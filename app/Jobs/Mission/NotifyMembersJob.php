<?php

namespace App\Jobs\Mission;

use App\Models\AppSetting;
use App\Models\Member;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Notification;

#[Queue('high')]
#[Tries(3)]
class NotifyMembersJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BaseNotification $notification,
    ) {}

    public function handle(): void
    {
        $excludeEmails = AppSetting::query()->where('key', 'organization.excluded_emails')->value('value');

        Member::query()
            ->whereNotIn('email', json_decode($excludeEmails))
            ->chunk(30, function ($members) {
                Notification::send($members, $this->notification);
            });
    }
}
