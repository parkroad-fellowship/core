<?php

namespace App\Notifications\PRFEvent;

use App\Models\PRFEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewEventNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public PRFEvent $prfEvent,
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
        $event = $this->prfEvent;

        return (new MailMessage)
            ->subject("New Event: {$event->name}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line($event->description)
            ->line("Start Date: {$event->start_date->format('D, d-M-Y')} at {$event->start_time}")
            ->line("End Date: {$event->end_date->format('D, d-M-Y')} at {$event->end_time}")
            ->line("Please visit the missions app to subscribe to this event and to view more details.")
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
