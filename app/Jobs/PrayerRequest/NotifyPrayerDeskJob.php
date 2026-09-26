<?php

namespace App\Jobs\PrayerRequest;

use App\Enums\PRFResponsibleDesk;
use App\Helpers\Utils;
use App\Models\PrayerRequest;
use App\Notifications\PrayerRequest\PrayerRequestReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Notification;

#[Queue('high')]
#[Tries(3)]
class NotifyPrayerDeskJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PrayerRequest $prayerRequest,
    ) {}

    public function handle(): void
    {
        $prayerRequest = $this->prayerRequest;

        Notification::send(
            Utils::deskRecipients(PRFResponsibleDesk::PRAYER_DESK),
            new PrayerRequestReceivedNotification($prayerRequest),
        );
    }
}
