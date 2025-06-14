<?php

namespace App\Notifications\Mission;

use App\Models\Mission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class FinancialsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Mission $mission,
        public string $fileName,
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

        $emails = [
            ...config('prf.app.missions_desk.emails'),
            ...config('prf.app.chairpersons_desk.emails'),
        ];

        return (new MailMessage)
            ->replyTo(config('prf.app.missions_desk.emails')[0])
            ->subject('Financial Report: '.$mission->school->name)
            ->cc($emails)
            ->greeting('Hello Treasurer,')
            ->line("Kindly find the financials of the mission to {$mission->school->name} linked in this email")
            ->line('Thank you for using our application!')
            ->attachData(Storage::get($this->fileName), $this->fileName);
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
