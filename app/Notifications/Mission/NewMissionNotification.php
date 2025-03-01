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
            ->line("A new mission has been created for {$mission->school->name}.")
            ->line("Type: {$mission->missionType->name}")
            ->line("Start Date: {$mission->start_date->format('D, d-M-Y')} at {$mission->start_time}")
            ->line("End Date: {$mission->end_date->format('D, d-M-Y')} at {$mission->end_time}")
            ->line('Please visit the missions app to subscribe to this mission and to view more details.')
            ->action('Google Play', 'https://play.google.com/store/apps/details?id=org.parkroadfellowship.app&hl=en')
            ->line('Thank you for using our application!');
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
