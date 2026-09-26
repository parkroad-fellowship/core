<?php

namespace App\Notifications\Mission;

use App\Contracts\HasTargetApp;
use App\Enums\PRFAppTopics;
use App\Enums\PRFEnvironment;
use App\Enums\PRFNotificationType;
use App\Models\Mission;
use App\Notifications\BaseNotification;
use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class MissionPostponedNotification extends BaseNotification implements HasTargetApp
{
    public function __construct(
        public Mission $mission,
        public ?Carbon $originalStartDate = null,
        public ?Carbon $originalEndDate = null,
    ) {}

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
        if ($this->shouldSendFcm($notifiable)) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mission = $this->mission;
        $mission->load(['school', 'missionType']);

        $appStores = config('prf.app.app_stores');

        $mailMessage = new MailMessage()
            ->replyTo(config('prf.app.missions_desk.emails')[0] ?? config('mail.from.address'))
            ->subject("Mission Postponed: {$mission->school->name}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line('⏰ **Mission Postponed**')
            ->line('')
            ->line('We need to inform you that the following mission has been postponed to new dates:')
            ->line('')
            ->line('**Mission Details:**')
            ->line("📍 **School:** {$mission->school->name}")
            ->line("📋 **Type:** {$mission->missionType->name}")
            ->line('');

        if (filled($mission->status_reason)) {
            $mailMessage->line("**Reason:** {$mission->status_reason}")->line('');
        }

        // Show original dates if provided
        if ($this->originalStartDate && $this->originalEndDate) {
            // Check if dates actually changed
            $datesChanged =
                !$this->originalStartDate->isSameDay($mission->start_date)
                || !$this->originalEndDate->isSameDay($mission->end_date);

            if ($datesChanged) {
                $mailMessage
                    ->line(
                        "**Original Dates:** {$this->originalStartDate->format(
                            'M j, Y',
                        )} - {$this->originalEndDate->format('M j, Y')} ❌",
                    )
                    ->line(
                        "**New Dates:** {$mission->start_date->format('M j, Y')} - {$mission->end_date->format(
     'M j, Y',
 )} ✅",
                    );
            } else {
                $mailMessage->line('ℹ️ Please keep an eye out for updated information.');
            }
        } else {
            $mailMessage->line(
                "📅 **New Dates:** {$mission->start_date->format('M j, Y')} - {$mission->end_date->format('M j, Y')}",
            );
        }

        return $mailMessage
            ->line('')
            ->line(
                '**Important:** If you were subscribed to this mission, your subscription remains active for the new dates.',
            )
            ->line('')
            ->line('**Action Required:** Please review the new dates and take appropriate action:')
            ->line("• If the new dates work for you - no action needed, you're still subscribed")
            ->line(
                "• If the new dates don't work - please inform the mission desk to be unsubscribed from this mission",
            )
            ->line(
                "• If you're unsure - check your availability and update the mission desk to update your subscription",
            )
            ->line('')
            ->line('**View more details in the app:**')
            ->line('')
            ->action('📱 Open Android App', $appStores['android']['url'])
            ->line('**Alternative Downloads:**')
            ->line("🍎 [iOS App Store]({$appStores['ios']['url']})")
            ->line("📲 [Huawei AppGallery]({$appStores['huawei']['url']})")
            ->line('')
            ->line('---')
            ->line('Thank you for your understanding and prompt action,')
            ->line('**PRF Missions Team**');
    }

    public function toFcm($notifiable)
    {
        $mission = $this->mission;
        $mission->load(['school', 'missionType']);
        $title = "Mission Postponed: {$mission->school->name}";
        $body = "The {$mission->missionType->name} mission to {$mission->school->name} has new dates.";

        return new FcmMessage(notification: new FcmNotification(title: $title, body: $body))->data([
            'type' => PRFNotificationType::MISSION_POSTPONED->value,
            'mission_ulid' => $mission->ulid,
            'target_app' => PRFAppTopics::MISSIONS_APP->value,
        ])->topic(PRFEnvironment::fromEnv(config('app.env'))->value . '_' . PRFAppTopics::MISSIONS_APP->value);
    }
}
