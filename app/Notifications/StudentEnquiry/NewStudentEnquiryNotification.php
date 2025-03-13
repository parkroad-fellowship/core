<?php

namespace App\Notifications\StudentEnquiry;

use App\Models\StudentEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewStudentEnquiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public StudentEnquiry $studentEnquiry,
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
        $studentEnquiry = $this->studentEnquiry;

        return (new MailMessage)
            ->subject('New Student Enquiry')
            ->greeting("Hello {$notifiable->full_name},")
            ->line('A student needs your help on the app. They have the following enquiry:')
            ->line($studentEnquiry->content)
            ->line('Please visit the missions app to view this enquiry.')
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
