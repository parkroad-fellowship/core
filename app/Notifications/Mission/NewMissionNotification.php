<?php

namespace App\Notifications\Mission;

use App\Models\Mission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMissionNotification extends Notification implements ShouldQueue
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
            ->subject("New Mission Available: {$mission->school->name}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line("🎯 **A new mission opportunity is available!**")
            ->line('')
            ->line("**Mission Details:**")
            ->line("📍 **School:** {$mission->school->name}")
            ->line("📋 **Type:** {$mission->missionType->name}")
            ->line('')
            ->line("**Ready to serve?** Subscribe to this mission through the PRF Missions app:")
            ->line('')
            ->action('📱 Download for Android', $appStores['android']['url'])
            ->line("**Alternative Downloads:**")
            ->line("🍎 [iOS App Store]({$appStores['ios']['url']})")
            ->line("📲 [Huawei AppGallery]({$appStores['huawei']['url']})")
            ->line('')
            ->line('---');
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
