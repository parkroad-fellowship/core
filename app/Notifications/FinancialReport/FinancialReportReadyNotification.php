<?php

namespace App\Notifications\FinancialReport;

use App\Filament\Resources\FinancialReports\FinancialReportResource;
use App\Models\FinancialReport;
use App\Models\User;
use App\Notifications\BaseNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as PanelNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Panel users get it in the notification bell with a download button, so the report is reachable
 * even where mail isn't configured; everyone also gets it by email.
 */
class FinancialReportReadyNotification extends BaseNotification
{
    /**
     * @param  bool  $emailOnly  "Email to me" in the panel: the person is already looking at the report.
     */
    public function __construct(
        public FinancialReport $report,
        public bool $emailOnly = false,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return !$this->emailOnly && $notifiable instanceof User ? ['database', 'mail'] : ['mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return PanelNotification::make()
            ->success()
            ->icon('heroicon-o-document-arrow-down')
            ->title("{$this->report->type->getLabel()} is ready")
            ->body($this->period())
            ->actions([
                Action::make('download')
                    ->label('Download')
                    ->button()
                    ->url(route('filament.admin.finance.reports.download', ['ulid' => $this->report->ulid], false))
                    ->markAsRead(),
                Action::make('open_reports')
                    ->label('All reports')
                    ->link()
                    ->url(FinancialReportResource::getUrl('index', isAbsolute: false, panel: 'admin')),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $report = $this->report;

        return new MailMessage()
            ->subject("{$report->type->getLabel()} is ready")
            ->line("Your {$report->type->getLabel()} ({$this->period()}) is attached.")
            ->line('You can also download it any time from Treasurer → Financial Reports.')
            ->attachData((string) FinancialReport::disk()->get((string) $report->file_path), $report->downloadName(), [
                'mime' => $report->type->mimeType(),
            ]);
    }

    private function period(): string
    {
        return "{$this->report->period_start->format('j M Y')} to {$this->report->period_end->format('j M Y')}";
    }
}
