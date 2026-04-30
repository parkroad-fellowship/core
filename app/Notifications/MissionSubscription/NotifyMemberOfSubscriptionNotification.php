<?php

namespace App\Notifications\MissionSubscription;

use App\Contracts\HasTargetApp;
use App\Enums\PRFAppTopics;
use App\Enums\PRFEnvironment;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\MissionSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class NotifyMemberOfSubscriptionNotification extends Notification implements HasTargetApp, ShouldQueue
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

    public function targetApp(object $notifiable): PRFAppTopics
    {
        return PRFAppTopics::MISSIONS_APP;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];
        if (! empty($notifiable->fcm_tokens)) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
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

        $appStores = config('prf.app.app_stores');

        // Status-specific messages and emojis
        [$statusEmoji, $statusMessage] = match ($missionSubscription->mission_subscription_status) {
            PRFMissionSubscriptionStatus::APPROVED => [
                '✅',
                "**Congratulations!** You have been approved for the {$mission->missionType->name} mission to {$mission->school->name}.",
            ],
            PRFMissionSubscriptionStatus::WITHDRAWN => [
                '❌',
                "You have been withdrawn from the {$mission->missionType->name} mission to {$mission->school->name}.",
            ],
            PRFMissionSubscriptionStatus::PENDING => [
                '⏳',
                "Your subscription for the {$mission->missionType->name} mission to {$mission->school->name} is currently under review.",
            ],
            PRFMissionSubscriptionStatus::FULLY_SUBSCRIBED => [
                '🔄',
                "The {$mission->missionType->name} mission to {$mission->school->name} is currently fully subscribed.",
            ],
            PRFMissionSubscriptionStatus::CONFLICT => [
                '⚠️',
                'There is a scheduling conflict with another mission you are approved for.',
            ],
            default => [
                '📝',
                "Your subscription for the {$mission->missionType->name} mission to {$mission->school->name} has been updated.",
            ],
        };

        $mailMessage = (new MailMessage)
            ->replyTo(config('prf.app.missions_desk.emails')[0] ?? config('mail.from.address'))
            ->subject("{$statusEmoji} {$missionSubscription->status_label}: {$mission->school->name}")
            ->greeting("Hello {$member->full_name},")
            ->line("{$statusEmoji} **Mission Subscription Update**")
            ->line('')
            ->line($statusMessage)
            ->line('')
            ->line('**Mission Details:**')
            ->line("📍 **School:** {$mission->school->name}")
            ->line("📋 **Type:** {$mission->missionType->name}")
            ->line("📅 **Dates:** {$mission->start_date->format('M j, Y')} - {$mission->end_date->format('M j, Y')}")
            ->line('');

        // Add specific content based on status
        if ($missionSubscription->mission_subscription_status === PRFMissionSubscriptionStatus::APPROVED) {
            $mailMessage
                ->line('🎯 **Prepare for Your Mission:**')
                ->line('To help you prepare effectively, please review these important resources:')
                ->line('')
                ->line('📚 **Mission Preparation Materials:**')
                ->line('• [Mission Guidelines & Expectations](http://bit.ly/43yfEtP)')
                ->line('• [Resource Preparation Guide](http://bit.ly/4iceBUU)')
                ->line('')
                ->line('**Next Steps:**')
                ->line('• Review all preparation materials thoroughly')
                ->line('• Join the mission WhatsApp group when created')
                ->line('• Prepare any required materials or resources')
                ->line('• Contact the mission desk if you have any questions')
                ->line('');
        } elseif ($missionSubscription->mission_subscription_status === PRFMissionSubscriptionStatus::PENDING) {
            $mailMessage
                ->line('⏳ **What happens next?**')
                ->line('• The mission desk will review your subscription')
                ->line('• You will be notified once a decision is made')
                ->line('• Please ensure your availability for the mission dates')
                ->line('');
        } elseif ($missionSubscription->mission_subscription_status === PRFMissionSubscriptionStatus::CONFLICT) {
            $mailMessage
                ->line('⚠️ **Action Required:**')
                ->line('Please contact the mission desk immediately to resolve the scheduling conflict.')
                ->line('We will work with you to find the best solution.')
                ->line('');
        } elseif ($missionSubscription->mission_subscription_status === PRFMissionSubscriptionStatus::FULLY_SUBSCRIBED) {
            $mailMessage
                ->line('📋 **Alternative Options:**')
                ->line('• You have been added to a waiting list in case of any changes')
                ->line('• Consider subscribing to other available missions')
                ->line('');
        }

        return $mailMessage
            ->line('**Stay Connected:** Access your mission information through the PRF app:')
            ->line('')
            ->action('📱 Open Android App', $appStores['android']['url'])
            ->line('**Alternative Downloads:**')
            ->line("🍎 [iOS App Store]({$appStores['ios']['url']})")
            ->line("📲 [Huawei AppGallery]({$appStores['huawei']['url']})")
            ->line('');
    }

    public function toFcm($notifiable)
    {
        $missionSubscription = $this->missionSubscription;
        $missionSubscription->load(['mission', 'mission.missionType', 'mission.school', 'member']);
        $mission = $missionSubscription->mission;
        $title = "Mission Subscription Update: {$mission->school->name}";
        $body = "Your subscription for the {$mission->missionType->name} mission to {$mission->school->name} is now {$missionSubscription->status_label}.";

        if ($missionSubscription->mission_subscription_status === PRFMissionSubscriptionStatus::FULLY_SUBSCRIBED) {
            $body = "The {$mission->missionType->name} mission to {$mission->school->name} is currently fully subscribed. You have been added to the waiting list.";
        } elseif ($missionSubscription->mission_subscription_status === PRFMissionSubscriptionStatus::CONFLICT) {
            $body = 'There is a scheduling conflict with another mission you are approved for. Please contact the mission desk to resolve.';
        }

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $body
        )))
            ->data([
                'type' => 'mission_subscription',
                'mission_ulid' => $mission->ulid,
                'subscription_status' => $missionSubscription->status_label,
                'target_app' => PRFAppTopics::MISSIONS_APP->value,
            ])->topic(
                PRFEnvironment::fromEnv(config('app.env'))->value
                .'_'
                .PRFAppTopics::MISSIONS_APP->value
            );
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
