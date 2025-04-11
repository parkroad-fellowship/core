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

        return (new MailMessage)
            ->subject("New Mission: {$mission->school->name}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line("New mission for {$mission->school->name}:")
            ->line("• Type: {$mission->missionType->name}")
            ->line("• Dates: {$mission->start_date->format('d-M-Y')} to {$mission->end_date->format('d-M-Y')}")
            ->line("• Times: {$mission->start_time} - {$mission->end_time}")
            ->line('')
            ->line('Subscribe in the app:')
            ->action('Open App', 'https://play.google.com/store/apps/details?id=org.parkroadfellowship.app&hl=en')
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
