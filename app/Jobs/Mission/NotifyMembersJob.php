<?php

namespace App\Jobs\Mission;

use App\Models\AppSetting;
use App\Models\Member;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification;

class NotifyMembersJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public BaseNotification $notification,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $excludeEmails = AppSetting::query()
            ->where('key', 'organization.excluded_emails')
            ->value('value');

        Member::query()
            ->whereNotIn('email', json_decode($excludeEmails))
            ->chunk(30, function ($members) {
                Notification::send(
                    $members,
                    $this->notification,
                );
            });
    }
}
