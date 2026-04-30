<?php

namespace App\Notifications\Mission;

use App\Enums\PRFResponsibleDesk;
use App\Helpers\Utils;
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
            ...Utils::getDeskEmails(PRFResponsibleDesk::MISSIONS_DESK),
            // ...Utils::getDeskEmails(PRFResponsibleDesk::CHAIRPERSON),
        ];

        return (new MailMessage)
            ->replyTo(config('prf.app.missions_desk.emails')[0] ?? config('mail.from.address'))
            ->subject("📊 Financial Report: {$mission->school->name}")
            ->cc($emails)
            ->greeting('Dear Treasurer,')
            ->line('📋 **Mission Financial Report Submission**')
            ->line('')
            ->line('Please find attached the comprehensive financial report for the recently completed mission.')
            ->line('')
            ->line('**Mission Details:**')
            ->line("📍 **School:** {$mission->school->name}")
            ->line("📋 **Type:** {$mission->missionType->name}")
            ->line("📅 **Mission Period:** {$mission->start_date->format('M j, Y')} - {$mission->end_date->format('M j, Y')}")
            ->line("📄 **Report File:** {$this->fileName}")
            ->line('')
            ->line('**Document Contents:**')
            ->line('• Detailed expense breakdown and receipts')
            ->line('• Disbursement and expenditure summary')
            ->line('')
            ->line('**Important:** This report has been automatically distributed to the missions desk and chairpersons for transparency and record-keeping purposes.')
            ->line('')
            ->line('Should you require any clarification or additional documentation, please contact the missions desk at your earliest convenience by replying to this email thread.')
            ->line('')
            ->line('---')
            ->attachData(Storage::get($this->fileName), $this->fileName, [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
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
