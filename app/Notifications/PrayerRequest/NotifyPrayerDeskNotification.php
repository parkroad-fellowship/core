<?php

namespace App\Notifications\PrayerRequest;

use App\Models\PrayerRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotifyPrayerDeskNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public PrayerRequest $prayerRequest,
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
        $prayerRequest = $this->prayerRequest;
        $prayerRequest->load(['member']);

        return (new MailMessage)
            ->replyTo($prayerRequest->member->email)
            ->subject("New Prayer Request: {$prayerRequest->title}")
            ->greeting('Hello Prayer Desk,')
            ->line("{$prayerRequest->member->full_name} has submitted a prayer request.")
            ->line('')
            ->line($prayerRequest->description)
            ->line('')
            ->action('View', route('filament.admin.resources.prayer-requests.edit', $prayerRequest->id))
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
