<?php

namespace App\Notifications\MissionGroundSuggestion;

use App\Models\MissionGroundSuggestion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotifyMissionDeskNotification extends Notification implements ShouldQueue
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
            ->subject("🎯 New Mission Ground Suggestion: {$missionGroundSuggestion->name}")
            ->greeting('Hello Mission Desk Team,')
            ->line('We have received a new mission ground suggestion that requires your attention.')
            ->line('')
            ->line('**Mission Details:**')
            ->line("• **Location:** {$missionGroundSuggestion->name}")
            ->line("• **Suggested by:** {$missionGroundSuggestion->suggestor->full_name}")
            ->line("• **Suggestor Email:** {$missionGroundSuggestion->suggestor->email}")
            ->line('')
            ->line('**Contact Information:**')
            ->line("• **Contact Person:** {$missionGroundSuggestion->contact_person}")
            ->line("• **Phone Number:** {$missionGroundSuggestion->contact_number}")
            ->line('')
            ->when($missionGroundSuggestion->description, function ($mail) use ($missionGroundSuggestion) {
                return $mail->line('**Additional Notes:**')
                    ->line($missionGroundSuggestion->description)
                    ->line('');
            })
            ->line('**Next Steps:**')
            ->line('• Review the suggestion details and reach out to the contact person')
            ->line('• Contact the suggestor if additional information is needed')
            ->line('• Update the suggestion status once reviewed')
            ->line('')
            ->action('📋 Review Suggestion', route('filament.admin.resources.mission-ground-suggestions.edit', $missionGroundSuggestion->id))
            ->line('')
            ->line('This suggestion was submitted on '.$missionGroundSuggestion->created_at->format('F j, Y \a\t g:i A'))
            ->line('')
            ->salutation('Thank you for your attention to this matter.');
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
