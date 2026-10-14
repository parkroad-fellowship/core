<?php

namespace App\Notifications\PrayerRequest;

use App\Models\PrayerRequest;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class PrayerRequestReceivedNotification extends BaseNotification
{
    public function __construct(
        public PrayerRequest $prayerRequest,
    ) {}

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

        return new MailMessage()
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
}
