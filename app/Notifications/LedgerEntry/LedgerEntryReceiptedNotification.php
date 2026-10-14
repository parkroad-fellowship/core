<?php

namespace App\Notifications\LedgerEntry;

use App\Models\LedgerEntry;
use App\Services\Finance\ImpactSummaryService;
use App\Services\Finance\ReceiptDocument;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Thanks a giver, with their PDF receipt attached and a short impact update.
 *
 * Sent with notifyNow() from DeliverJob, which records whether the send succeeded.
 */
class LedgerEntryReceiptedNotification extends Notification
{
    public function __construct(
        public LedgerEntry $ledgerEntry,
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
        $receipts = app(ReceiptDocument::class);
        $impact = app(ImpactSummaryService::class)->yearToDate($this->ledgerEntry->transacted_on);
        $entry = $this->ledgerEntry;

        $mail = new MailMessage()
            ->subject("Thank you for your gift — Receipt {$entry->receipt_number}")
            ->greeting('Thank you!')
            ->line($receipts->acknowledgement($entry))
            ->line("**Your giving at work ({$impact->periodLabel()}):**")
            ->line("- {$impact->missionsServiced} missions served")
            ->line('- ' . number_format($impact->studentsReached) . ' students reached')
            ->line("- {$impact->souls} souls won for Christ")
            ->line("- {$impact->inDiscipleship} believers in discipleship")
            ->line('Your receipt is attached.')
            ->salutation('With gratitude, the Treasurer');

        return $mail->attachData($receipts->pdf($entry), $receipts->filename($entry), ['mime' => 'application/pdf']);
    }
}
