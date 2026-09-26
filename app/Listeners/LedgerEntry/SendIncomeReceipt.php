<?php

namespace App\Listeners\LedgerEntry;

use App\Enums\PRFReceiptChannel;
use App\Events\LedgerEntry\IncomeReceipted;
use App\Jobs\ReceiptDelivery\CreateJob;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

/**
 * Sends the giver their receipt by email and SMS, when the treasurer asked for it and the
 * giver left contacts. WhatsApp links are created on demand from the cashbook.
 */
class SendIncomeReceipt implements ShouldHandleEventsAfterCommit
{
    public function handle(IncomeReceipted $event): void
    {
        $entry = $event->ledgerEntry;

        if (!$event->sendReceipt) {
            return;
        }

        $channels = array_filter([
            PRFReceiptChannel::EMAIL->value => $entry->giver_email,
            PRFReceiptChannel::SMS->value => $entry->giver_phone,
        ]);

        foreach (array_keys($channels) as $channel) {
            CreateJob::dispatchSync([
                'ledger_entry_ulid' => $entry->ulid,
                'channel' => $channel,
                'requested_by' => $entry->recorded_by,
            ]);
        }
    }
}
