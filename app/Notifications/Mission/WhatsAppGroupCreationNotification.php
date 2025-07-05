<?php

namespace App\Notifications\Mission;

use App\Models\Mission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WhatsAppGroupCreationNotification extends Notification
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
            ->subject("🗨️ WhatsApp Group Ready: {$mission->school->name}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line('📱 **Great news! Your mission WhatsApp group is ready!**')
            ->line('')
            ->line('We\'ve created a dedicated WhatsApp group to help you stay connected with your fellow volunteers and receive important updates throughout your mission.')
            ->line('')
            ->line('**Mission Details:**')
            ->line("📍 **School:** {$mission->school->name}")
            ->line("📋 **Type:** {$mission->missionType->name}")
            ->line("📅 **Dates:** {$mission->start_date->format('M j, Y')} - {$mission->end_date->format('M j, Y')}")
            ->line('')
            ->line('**Why Join the Group?**')
            ->line('• 🔔 Receive real-time mission updates and announcements')
            ->line('• 🤝 Connect and coordinate with your fellow missioners')
            ->line('• ❓ Get quick answers to mission-related questions')
            ->line('• 📚 Share resources and helpful tips')
            ->line('• 🎯 Stay informed about any schedule changes')
            ->line('')
            ->line('**Ready to connect?** Join your mission WhatsApp group now:')
            ->line('')
            ->action('💬 Join WhatsApp Group', $mission->whats_app_link)
            ->line('')
            ->line('**Stay Updated:** You can also access mission information through the PRF app:')
            ->line('')
            ->action('📱 Open Android App', $appStores['android']['url'])
            ->line('**Alternative Downloads:**')
            ->line("🍎 [iOS App Store]({$appStores['ios']['url']})")
            ->line("📲 [Huawei AppGallery]({$appStores['huawei']['url']})")
            ->line('')
            ->line('---')
            ->line('We\'re excited to have you as part of this mission team!')
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
