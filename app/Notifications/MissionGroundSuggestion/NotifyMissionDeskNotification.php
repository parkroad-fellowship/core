<?php

namespace App\Notifications\MissionGroundSuggestion;

use App\Models\MissionGroundSuggestion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotifyMissionDeskNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public MissionGroundSuggestion $missionGroundSuggestion,
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
        $missionGroundSuggestion = $this->missionGroundSuggestion;
        $missionGroundSuggestion->load(['suggestor']);

        return (new MailMessage)
            ->replyTo($missionGroundSuggestion->suggestor->email)
            ->subject("New Mission Ground Suggestion: {$missionGroundSuggestion->name}")
            ->greeting('Hello Mission Desk,')
            ->line('')
            ->line("{$missionGroundSuggestion->suggestor->full_name} has suggested a mission to {$missionGroundSuggestion->name}")
            ->line("Contact Person: {$missionGroundSuggestion->contact_person}")
            ->line("Contact Number: {$missionGroundSuggestion->contact_number}")
            ->line('')
            ->action('View', route('filament.admin.resources.mission-ground-suggestions.edit', $missionGroundSuggestion->id))
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
