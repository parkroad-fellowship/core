<?php

namespace App\Notifications\LedgerImport;

use App\Enums\PRFProcessingStatus;
use App\Models\LedgerImport;
use App\Notifications\BaseNotification;
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
        return ['mail'];
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
