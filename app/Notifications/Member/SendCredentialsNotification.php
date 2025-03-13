<?php

namespace App\Notifications\Member;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendCredentialsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct() {}

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
        return (new MailMessage)
            ->cc($notifiable->personal_email)
            ->subject("Access PRF Missions")
            ->greeting("Hello {$notifiable->full_name},")
            ->line("Your email address is: {$notifiable->email}")
            ->line("Your password is: prf@2025*")
            ->line('')
            ->line('You will be asked to reset your password the first time you log in.')
            ->line('')
            ->action('Download PRF Missions', 'https://play.google.com/store/apps/details?id=org.parkroadfellowship.app&hl=en')
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
