<?php

namespace App\Notifications\Member;

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
            ->replyTo(config('prf.app.missions_desk.emails')[0])
            ->subject('🎉 Welcome to PRF Missions! Your Account is Ready! 🚀')
            ->greeting("Hello {$notifiable->full_name}! 👋")
            ->line('**Congratulations!** Your PRF Missions account has been successfully created and is ready to use.')
            ->line('')
            ->line('**📧 Your Login Credentials:**')
            ->line('Use these credentials to log in with Google (just like you normally would with any Google account):')
            ->line('')
            ->line("📧 **Email Address:** {$notifiable->email}")
            ->line('🔑 **Temporary Password:** prf@2025* (note the asterisk/star at the end)')
            ->line('')
            ->line('💡 **Login Instructions:**')
            ->line('1. Open the PRF Missions app')
            ->line('2. Tap "Sign in with Google"')
            ->line('3. Enter the email address above (copy and paste to avoid typos)')
            ->line('4. Enter the password: prf@2025* (includes the asterisk symbol)')
            ->line('')
            ->line('🔐 **Important Security Note:** This is a temporary password. You will be prompted to create your own secure password when you first log in.')
            ->line('')
            ->line('� **Download the PRF Missions App:**')
            ->line('Get started by downloading our mobile app from your preferred platform:')
            ->line('')
            ->line('**Android (Google Play Store):**')
            ->line(config('prf.app.app_stores.android.url'))
            ->line('')
            ->line('**iOS (Apple App Store):**')
            ->line(config('prf.app.app_stores.ios.url'))
            ->line('')
            ->line('**Huawei (AppGallery):**')
            ->line(config('prf.app.app_stores.huawei.url'))
            ->line('')
            ->line('🌟 **What\'s Next?**')
            ->line('1. Download the app from your preferred store')
            ->line('2. Log in with your credentials above')
            ->line('3. Set up your secure password')
            ->line('4. Start exploring PRF Missions!')
            ->line('')
            ->line('� **Need Help?**')
            ->line('If you have any questions or need assistance, feel free to reach out to our missions desk.')
            ->line('')
            ->line("We're thrilled to have you as part of the PRF Missions community! 🙌")
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
