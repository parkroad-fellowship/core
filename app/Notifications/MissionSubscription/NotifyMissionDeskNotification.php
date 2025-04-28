<?php

namespace App\Notifications\MissionSubscription;

use App\Models\MissionSubscription;
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
        public MissionSubscription $missionSubscription,
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
        $missionSubscription = $this->missionSubscription;
        $missionSubscription->load(['mission', 'mission.missionType', 'mission.school', 'member']);

        $mission = $missionSubscription->mission;
        $member = $missionSubscription->member;

        return (new MailMessage)
            ->subject("New Subscriber: {$mission->school->name}")
            ->greeting('Hello Mission Desk,')
            ->line("{$member->full_name} has subscribed to {$mission->school->name}")
            ->line("• Type: {$mission->missionType->name}")
            ->line("• Dates: {$mission->start_date->format('d-M-Y')} to {$mission->end_date->format('d-M-Y')}")
            ->line('')
            ->action('View', route('filament.admin.resources.missions.edit', $mission->id))
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
