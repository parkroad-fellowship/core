<?php

namespace App\Listeners\MissionSubscription;

use App\Events\MissionSubscription\CreatedEvent;
use App\Models\AppSetting;
use App\Models\Member;
use App\Notifications\MissionSubscription\NotifyMissionDeskNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class CreatedListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CreatedEvent $event): void
    {
        $missionSubscription = $event->missionSubscription;

        Notification::send(
            Member::whereIn('email', AppSetting::get('desk_emails.missions', []))->get(),
            new NotifyMissionDeskNotification($missionSubscription),
        );
    }
}
