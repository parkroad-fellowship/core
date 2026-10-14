<?php

namespace App\Listeners\MissionSubscription;

use App\Enums\PRFResponsibleDesk;
use App\Events\MissionSubscription\MissionSubscriptionCreated;
use App\Helpers\Utils;
use App\Notifications\MissionSubscription\MissionSubscriptionReceivedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Fires for every new subscription, whether it came from the app or the admin panel.
 */
class NotifyMissionDeskOfSubscription
{
    public function handle(MissionSubscriptionCreated $event): void
    {
        Notification::route(
            'mail',
            Utils::getDeskEmails(PRFResponsibleDesk::MISSIONS_DESK),
        )->notify(new MissionSubscriptionReceivedNotification($event->missionSubscription));
    }
}
