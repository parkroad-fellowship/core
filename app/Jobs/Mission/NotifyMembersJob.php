<?php

namespace App\Jobs\Mission;

use App\Models\Member;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification;

class NotifyMembersJob implements ShouldQueue
{
    use Dispatchable;
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

        Member::query()
            ->where('is_desk_email', false)
            ->chunk(30, function ($members) {
                Notification::send(
                    $members,
                    $this->notification,
                );
            });
    }
}
