<?php

namespace App\Notifications\Mission;

use App\Models\Mission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

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
        return [
            OneSignalChannel::class,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line("New mission: {$this->mission->school->name}")
            ->action('', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }

    public function toOneSignal(object $notifiable): OneSignalMessage
    {
        return OneSignalMessage::create()
            ->setSubject('(New Mission) '.$this->mission->school->name)
            ->setBody(
                'A new mission has been created for '.$this->mission->school->name.'. 
            Please visit the missions app to subscribe.\n\n
            Start Date: '.$this->mission->start_date.'\n
            End Date: '.$this->mission->end_date.'\n
            Start Time: '.$this->mission->start_time.'\n
            End Time: '.$this->mission->end_time.'\n\n
            Thank you for using our application!'
            );
    }
}
