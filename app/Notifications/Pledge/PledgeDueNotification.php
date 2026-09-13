<?php

namespace App\Notifications\Pledge;

use App\Models\Pledge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PledgeDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Pledge $pledge,
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
        $pledge = $this->pledge;
        $dueOn = $pledge->next_due_on?->format('F j, Y') ?? 'soon';

        return new MailMessage()
            ->subject('A gentle reminder about your PRF pledge')
            ->greeting("Hello {$pledge->name},")
            ->line("Thank you for your commitment to support the fellowship. Your next pledge is due on **{$dueOn}**.")
            ->line('')
            ->line('If you have already given, thank you so much — no further action is needed.')
            ->line('')
            ->action('Give', route('pledges.page'))
            ->line('With gratitude, the PRF Treasurer.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'pledge_ulid' => $this->pledge->ulid,
        ];
    }
}
