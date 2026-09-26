<?php

namespace App\Jobs\ReceiptDelivery;

use App\Enums\PRFDeliveryStatus;
use App\Enums\PRFReceiptChannel;
use App\Models\LedgerEntry;
use App\Models\ReceiptDelivery;
use App\Services\Finance\ReceiptDocument;
use Illuminate\Foundation\Bus\Dispatchable;
use InvalidArgumentException;

/**
 * Sends an income receipt to a giver over one channel. Email and SMS go out on the queue;
 * WhatsApp returns a share link the treasurer opens to send it from their own phone.
 */
class CreateJob
{
    use Dispatchable;

    /**
     * @param  array{ledger_entry_ulid: string, channel: int|PRFReceiptChannel, recipient?: string|null, requested_by?: int|null}  $data
     */
    public function __construct(
        public array $data,
    ) {}

    public function handle(ReceiptDocument $receipts): ReceiptDelivery
    {
        $entry = LedgerEntry::query()->where('ulid', $this->data['ledger_entry_ulid'])->firstOrFail();

        if ($entry->receipt_number === null) {
            throw new InvalidArgumentException('Only receipted income can be sent to a giver.');
        }

        $channel = $this->data['channel'] instanceof PRFReceiptChannel
            ? $this->data['channel']
            : PRFReceiptChannel::from((int) $this->data['channel']);

        $recipient = $this->data['recipient'] ?? match ($channel) {
            PRFReceiptChannel::EMAIL => $entry->giver_email,
            PRFReceiptChannel::SMS, PRFReceiptChannel::WHATSAPP => $entry->giver_phone,
        };

        if (blank($recipient)) {
            throw new InvalidArgumentException("No {$channel->getLabel()} contact to send the receipt to.");
        }

        $delivery = ReceiptDelivery::create([
            'ledger_entry_id' => $entry->id,
            'channel' => $channel,
            'recipient' => $recipient,
            'status' => $channel === PRFReceiptChannel::WHATSAPP
                ? PRFDeliveryStatus::LINK_READY
                : PRFDeliveryStatus::PENDING,
            'share_url' => $channel === PRFReceiptChannel::WHATSAPP ? $receipts->whatsAppURL($entry, $recipient) : null,
            'requested_by' => $this->data['requested_by'] ?? null,
        ]);

        if ($channel !== PRFReceiptChannel::WHATSAPP) {
            DeliverJob::dispatch($delivery);
        }

        return $delivery;
    }
}
