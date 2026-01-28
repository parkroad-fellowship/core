<?php

namespace App\Notifications\Mission;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ExecutiveSummariesReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $fileName,
        public int $missionCount,
        public ?string $dateRange = null,
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
        $message = (new MailMessage)
            ->replyTo(config('prf.app.missions_desk.emails')[0])
            ->subject('📊 Mission Executive Summaries Report - '.now()->format('M d, Y'))
            ->greeting('Mission Executive Summaries Report')
            ->line("📋 Attached is a comprehensive report containing executive summaries for **{$this->missionCount} missions**.");

        if ($this->dateRange) {
            $message->line("📅 **Report Period:** {$this->dateRange}");
        }

        $message->line('')
            ->line('**Document Contents:**')
            ->line('• AI-generated executive summaries for each mission')
            ->line('• Mission details (school, date, theme, team size)')
            ->line('• Impact metrics (souls reached)')
            ->line('• Professional formatting for stakeholder review')
            ->line('')
            ->line('Please review the attached PDF document for detailed information.')
            ->line('')
            ->line('---')
            ->attachData(
                Storage::get($this->fileName),
                basename($this->fileName),
                ['mime' => 'application/pdf']
            );

        return $message;
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
}
