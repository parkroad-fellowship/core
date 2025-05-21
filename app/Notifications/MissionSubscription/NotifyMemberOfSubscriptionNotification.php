<?php

namespace App\Notifications\MissionSubscription;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\MissionSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotifyMemberOfSubscriptionNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public MissionSubscription $missionSubscription,
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $missionSubscription = $this->missionSubscription;
        $missionSubscription->load(['mission', 'mission.missionType', 'mission.school', 'member']);

        $mission = $missionSubscription->mission;
        $member = $missionSubscription->member;

        $message = match ($missionSubscription->mission_subscription_status) {
            PRFMissionSubscriptionStatus::APPROVED => "You have been approved for the {$mission->missionType->name} mission to {$mission->school->name}.",
            PRFMissionSubscriptionStatus::WITHDRAWN => "You have been withdrawn for the {$mission->missionType->name} mission to {$mission->school->name}.",
            PRFMissionSubscriptionStatus::PENDING => "Your subscription for the {$mission->missionType->name} mission to {$mission->school->name} is pending. Please wait for the mission desk to approve your subscription.",
            PRFMissionSubscriptionStatus::FULLY_SUBSCRIBED => "This {$mission->missionType->name} mission to {$mission->school->name} is currently fully subscribed. We can no longer accept new subscriptions."
        };

        return (new MailMessage)
            ->subject("{$missionSubscription->status_label}: {$mission->school->name}")
            ->greeting("Hello {$member->full_name},")
            ->line($message)
            ->line('')
            ->line("Dates: {$mission->start_date->format('F j, Y')} - {$mission->end_date->format('F j, Y')}")
            ->line('')
            ->action('View', 'https://play.google.com/store/apps/details?id=org.parkroadfellowship.app&hl=en')
            ->line('');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
