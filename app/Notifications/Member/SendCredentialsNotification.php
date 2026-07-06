<?php

namespace App\Notifications\Member;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendCredentialsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to Hossana Missions! Your Account is Ready')
            ->greeting("Hello {$notifiable->full_name}!")
            ->line('Your Hossana Missions account has been successfully created and is ready to use.')
            ->line('')
            ->line('**Sign In Instructions:**')
            ->line('1. Open the app')
            ->line('2. Tap "Sign in with Google"')
            ->line('3. Use your Google account to sign in')
            ->line('')
            ->line('**Need Help?**')
            ->line('If you have any questions or need assistance, feel free to reach out.')
            ->line('')
            ->line("We're thrilled to have you as part of the HMT community!");
    }

    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
