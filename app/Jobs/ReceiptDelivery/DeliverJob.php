<?php

namespace App\Jobs\ReceiptDelivery;

use App\Contracts\Services\SMSGatewayInterface;
use App\Enums\PRFDeliveryStatus;
use App\Enums\PRFReceiptChannel;
use App\Exceptions\IntegrationNotConfiguredException;
use App\Models\ReceiptDelivery;
use App\Notifications\LedgerEntry\LedgerEntryReceiptedNotification;
use App\Services\Finance\ReceiptDocument;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

#[Queue('high')]
#[Tries(3)]
#[Backoff([30, 120])]
#[Timeout(120)]
class DeliverJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public function __construct(
        public ReceiptDelivery $delivery,
    ) {}

    public function uniqueId(): string
    {
        return $this->delivery->ulid;
    }

    public function handle(SMSGatewayInterface $sms, ReceiptDocument $receipts): void
    {
        $delivery = $this->delivery;
        $entry = $delivery->ledgerEntry()->firstOrFail();

        try {
            match ($delivery->channel) {
                PRFReceiptChannel::EMAIL => Notification::route('mail', $delivery->recipient)->notifyNow(
                    new LedgerEntryReceiptedNotification($entry),
                ),
                PRFReceiptChannel::SMS => $sms->send($delivery->recipient, $receipts->smsText($entry), $entry),
                PRFReceiptChannel::WHATSAPP => null,
            };
        } catch (IntegrationNotConfiguredException $exception) {
            // Retrying won't help until the treasurer configures the provider.
            $delivery->update(['status' => PRFDeliveryStatus::FAILED, 'error' => $exception->getMessage()]);

            return;
        }

        $delivery->update(['status' => PRFDeliveryStatus::SENT, 'sent_at' => now(), 'error' => null]);
    }

    public function failed(Throwable $exception): void
    {
        $this->delivery->update(['status' => PRFDeliveryStatus::FAILED, 'error' => $exception->getMessage()]);

        Log::error('Receipt delivery failed', [
            'delivery' => $this->delivery->ulid,
            'error' => $exception->getMessage(),
        ]);
    }
}
