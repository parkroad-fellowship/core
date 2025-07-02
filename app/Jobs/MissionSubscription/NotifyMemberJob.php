<?php

namespace App\Jobs\MissionSubscription;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Helpers\Utils;
use App\Models\MissionSubscription;
use App\Notifications\Mission\WhatsAppGroupCreationNotification;
use App\Notifications\MissionSubscription\NotifyMemberOfSubscriptionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class NotifyMemberJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MissionSubscription $missionSubscription,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $missionSubscription = $this->missionSubscription;
        $missionSubscription->load(['member']);

        $member = $missionSubscription->member;

        Notification::send(
            $member,
            new NotifyMemberOfSubscriptionNotification($missionSubscription)
        );

        // If the mission has a WhatsApp group link and the member hasn't been invited yet
        // send them a notification about the group creation
        $mission = $missionSubscription->mission;

        if (
            Utils::checkWhatsAppGroupLink(link: $mission->whats_app_link)
            && $missionSubscription->mission_subscription_status === PRFMissionSubscriptionStatus::APPROVED
            && ! $missionSubscription->invited_to_group
        ) {
            Notification::send(
                $member,
                new WhatsAppGroupCreationNotification($mission),
            );

            // Update the subscription to indicate the member has been invited to the group
            $missionSubscription->update([
                'invited_to_group' => true,
                'invited_to_group_at' => now(),
            ]);
        }
    }
}
