<?php

namespace App\Jobs\Payment;

use App\Enums\PRFPaymentStatus;
use App\Jobs\Middleware\SkipWhenIntegrationMissing;
use App\Models\Payment;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Middleware\RateLimited;

/**
 * Poller fallback for payments whose webhook has not arrived: checks one payment, then
 * schedules its next check further out (2, 5, 10, 30, then every 60 minutes).
 */
#[Queue('high')]
#[Tries(3)]
class VerifyStatusJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /**
     * Minutes to wait after the Nth check.
     */
    public const BACKOFF_MINUTES = [2, 5, 10, 30, 60];

    public function __construct(
        public Payment $payment,
    ) {}

    public function uniqueId(): string
    {
        return $this->payment->ulid;
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RateLimited('paystack'), new SkipWhenIntegrationMissing()];
    }

    public function handle(): void
    {
        $payment = CheckStatusJob::dispatchSync($this->payment);

        if ($payment->payment_status === PRFPaymentStatus::INITIALISED) {
            $payment->update([
                'next_status_check_at' => now()->addMinutes(self::nextDelay($payment->status_check_count)),
            ]);
        }
    }

    public static function nextDelay(int $checksSoFar): int
    {
        return self::BACKOFF_MINUTES[min($checksSoFar, count(self::BACKOFF_MINUTES) - 1)];
    }
}
