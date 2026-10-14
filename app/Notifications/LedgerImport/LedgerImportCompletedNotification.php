<?php

namespace App\Notifications\LedgerImport;

use App\Enums\PRFProcessingStatus;
use App\Filament\Pages\ImportWorkbook;
use App\Filament\Resources\LedgerEntries\LedgerEntryResource;
use App\Models\LedgerImport;
use App\Notifications\BaseNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification as PanelNotification;
use Illuminate\Notifications\Messages\MailMessage;

class LedgerImportCompletedNotification extends BaseNotification
{
    public function __construct(
        public LedgerImport $ledgerImport,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $import = $this->ledgerImport;
        $summary = $import->summary ?? [];
        $completed = $import->status === PRFProcessingStatus::COMPLETED;
        $count = fn(string $key): int => is_numeric($summary[$key] ?? null) ? (int) $summary[$key] : 0;

        return PanelNotification::make()
            ->status($completed ? 'success' : 'danger')
            ->title("Workbook import {$import->status->getLabel()}: {$import->original_name}")
            ->body(
                $completed
                    ? sprintf(
                        '%d lines posted, %d skipped as duplicates, %d left unmapped.',
                        $count('posted'),
                        $count('skipped'),
                        $count('unmapped'),
                    )
                    : $import->error ?? 'No further details recorded.',
            )
            ->actions([
                Action::make('open')
                    ->label($completed ? 'Open the cashbook' : 'Try again')
                    ->button()
                    ->url(
                        $completed
                            ? LedgerEntryResource::getUrl('index', isAbsolute: false, panel: 'admin')
                            : ImportWorkbook::getUrl(isAbsolute: false, panel: 'admin'),
                    )
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $import = $this->ledgerImport;
        $summary = $import->summary ?? [];
        $mail = new MailMessage()->subject("Workbook import {$import->status->getLabel()}: {$import->original_name}");

        if ($import->status === PRFProcessingStatus::COMPLETED) {
            $posted = $summary['posted'] ?? 0;
            $skipped = $summary['skipped'] ?? 0;
            $unmapped = $summary['unmapped'] ?? 0;
            $paired = $summary['transfers_paired'] ?? 0;

            $mail
                ->line(
                    "Your {$import->year} workbook import finished: {$posted} lines posted, {$skipped} skipped as duplicates, {$unmapped} left unmapped.",
                )
                ->line("Transfers paired: {$paired}. Unpaired transfers were booked as single transfer lines.")
                ->line('Open Treasurer → Cashbook to review the imported lines.');
        } else {
            $mail
                ->line("Your {$import->year} workbook import failed.")
                ->line($import->error ?? 'No further details recorded.')
                ->line('Please try uploading the workbook again, or ask for help.');
        }

        return $mail;
    }
}
