<?php

namespace App\Notifications\AccountingEvent;

use App\Models\AccountingEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class FinancialsNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public AccountingEvent $accountingEvent,
        public string $fileName
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
        $accountingEvent = $this->accountingEvent;

        return (new MailMessage)
            ->replyTo(config('prf.app.missions_desk.emails')[0] ?? config('mail.from.address'))
            ->subject("📊 Financial Report: {$accountingEvent->name}")
            ->greeting('Dear Treasurer,')
            ->line('📋 **Financial Report Submission**')
            ->line('')
            ->line('Please find attached the comprehensive financial report for the event.')
            ->line('')
            ->line("📄 **Report File:** {$this->fileName}")
            ->line('')
            ->line('**Document Contents:**')
            ->line('• Detailed expense breakdown and receipts')
            ->line('• Disbursement and expenditure summary')
            ->line('')
            ->line('**Important:** This report has been automatically distributed to the desk and chairperson for transparency and record-keeping purposes.')
            ->line('')
            ->line('Should you require any clarification or additional documentation, please contact the desk at your earliest convenience by replying to this email thread.')
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
