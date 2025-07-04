<?php

namespace App\Notifications\Mission;

use App\Models\Mission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ThankYouNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Mission $mission,
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
        $mission = $this->mission;
        $mission->load(['school', 'missionType']);

        $appStores = config('prf.app.app_stores');

        return (new MailMessage)
            ->replyTo(config('prf.app.missions_desk.emails')[0])
            ->subject("Thank You for Your Service: {$mission->school->name}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line('🙏 **Thank you for your incredible service!**')
            ->line('')
            ->line('We want to express our heartfelt gratitude for your participation in the recent mission:')
            ->line('')
            ->line('**Mission Details:**')
            ->line("📍 **School:** {$mission->school->name}")
            ->line("📋 **Type:** {$mission->missionType->name}")
            ->line("📅 **Dates:** {$mission->start_date->format('M j, Y')} - {$mission->end_date->format('M j, Y')}")
            ->line('')
            ->line('**Your Impact:** Your dedication and commitment have made a real difference in the lives of the children and educators at this school.')
            ->line('')
            ->line('**Recognition:** Your time, effort, and heart do not go unnoticed. We hope this experience has been as rewarding for you as it has been meaningful for those you\'ve served.')
            ->line('')
            ->line('**Stay Connected:** Keep track of your mission history and discover new opportunities through the PRF app:')
            ->line('')
            ->action('📱 Open Android App', $appStores['android']['url'])
            ->line('**Alternative Downloads:**')
            ->line("🍎 [iOS App Store]({$appStores['ios']['url']})")
            ->line("📲 [Huawei AppGallery]({$appStores['huawei']['url']})")
            ->line('')
            ->line('---')
            ->line('Once again, thank you for being an incredible part of our mission. We look forward to having you join us again in future endeavors!')
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
