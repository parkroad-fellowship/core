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

        return (new MailMessage)
            ->replyTo(config('prf.app.missions_desk.emails')[0])
            ->subject("WhatsApp Group Created for {$mission->school->name}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line("A WhatsApp group has been created for the mission at {$mission->school->name}.")
            ->line("• Type: {$mission->missionType->name}")
            ->line("• Dates: {$mission->start_date->format('d-M-Y')} to {$mission->end_date->format('d-M-Y')}")
            ->line('')
            ->line('Join the WhatsApp group to stay updated:')
            ->action('Join Group', url($mission->whats_app_link))
            ->line('Thank you!');
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
