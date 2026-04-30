<?php

namespace App\Jobs\PrayerRequest;

use App\Models\AppSetting;
use App\Models\Member;
use App\Models\PrayerRequest;
use App\Notifications\PrayerRequest\NotifyPrayerDeskNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyPrayerDeskJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PrayerRequest $prayerRequest,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $prayerRequest = $this->prayerRequest;

        Notification::send(
            Member::whereIn('email', AppSetting::get('desk_emails.prayer', []))->get(),
            new NotifyPrayerDeskNotification($prayerRequest)
        );
    }
}
