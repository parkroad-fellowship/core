<?php

namespace App\Notifications\FinancialReport;

use App\Models\FinancialReport;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;

class FinancialReportReadyNotification extends BaseNotification
{
    public function __construct(
        public FinancialReport $report,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $report = $this->report;

        return new MailMessage()
            ->subject("{$report->type->getLabel()} is ready")
            ->line(
                "Your {$report->type->getLabel()} for {$report->period_start->format(
     'j M Y',
 )} to {$report->period_end->format('j M Y')} is attached.",
            )
            ->line('You can also download it any time from Treasurer → Financial Reports.')
            ->attachData(
                (string) Storage::disk(FinancialReport::DISK)->get((string) $report->file_path),
                $report->downloadName(),
                ['mime' => $report->type->mimeType()],
            );
    }
}
