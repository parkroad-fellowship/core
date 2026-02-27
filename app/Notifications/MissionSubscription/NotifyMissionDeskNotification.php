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
            ->replyTo(config('prf.app.missions_desk.emails')[0] ?? config('mail.from.address'))
            ->subject("🎯 New Mission Volunteer: {$mission->school->name}")
            ->greeting('Hello Mission Desk Team,')
            ->line('📝 **Exciting News! We have a new mission volunteer!**')
            ->line('')
            ->line('A dedicated member has just subscribed to join one of our upcoming missions.')
            ->line('')
            ->line('**Volunteer Information:**')
            ->line("👤 **Name:** {$member->full_name}")
            ->line("📧 **Email:** {$member->email}")
            ->line('📱 **Phone:** '.($member->phone_number ?? 'Not provided'))
            ->line('')
            ->line('**Mission Details:**')
            ->line("📍 **School:** {$mission->school->name}")
            ->line("📋 **Type:** {$mission->missionType->name}")
            ->line("📅 **Dates:** {$mission->start_date->format('M j, Y')} - {$mission->end_date->format('M j, Y')}")
            ->line("🆔 **Mission Role:** {$missionSubscription->mission_role_label}")
            ->line("📊 **Status:** {$missionSubscription->status_label}")
            ->line('')
            ->line('**Next Steps:**')
            ->line('• Review the volunteer\'s subscription details')
            ->line('• Approve or manage their mission participation')
            ->line('• Ensure proper mission coordination and communication')
            ->line('• Add them to any relevant mission planning discussions')
            ->line('')
            ->line('**Quick Actions:** Access the mission management portal to review and process this subscription:')
            ->line('')
            ->action('🔧 Manage Mission', route('filament.admin.resources.missions.edit', $mission->id))
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
