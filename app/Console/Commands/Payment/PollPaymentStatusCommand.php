<?php

namespace App\Console\Commands\Payment;

use App\Console\Concerns\RunsForEachTenant;
use App\Enums\PRFPaymentStatus;
use App\Jobs\Payment\VerifyStatusJob;
use App\Models\Payment;
use Illuminate\Console\Command;

/**
 * Fallback for missed Paystack webhooks. Each run only touches payments that are due for a
 * check (with growing gaps between checks) and gives up on checkouts older than a day.
 */
class PollPaymentStatusCommand extends Command
{
    use RunsForEachTenant;

    public const MAX_AGE_HOURS = 24;

    protected $signature = 'prf:payments:poll-status';

    protected $description = 'Queue status checks for open payments and expire stale ones';

    public function handle(): int
    {
        $queued = 0;
        $expired = 0;

        $this->forEachTenant(function () use (&$queued, &$expired): void {
            $cutoff = now()->subHours(self::MAX_AGE_HOURS);

            Payment::query()
                ->whereIn('payment_status', [PRFPaymentStatus::PENDING->value, PRFPaymentStatus::INITIALISED->value])
                ->where('created_at', '<', $cutoff)
                ->lazyById()
                ->each(function (Payment $payment) use (&$expired): void {
                    $payment->update(['payment_status' => PRFPaymentStatus::EXPIRED]);
                    $expired++;
                });

            Payment::query()
                ->where('payment_status', PRFPaymentStatus::INITIALISED->value)
                ->where('created_at', '>=', $cutoff)
                ->where(fn($query) => $query->whereNull('next_status_check_at')->orWhere(
                    'next_status_check_at',
                    '<=',
                    now(),
                ))
                ->lazyById()
                ->each(function (Payment $payment) use (&$queued): void {
                    VerifyStatusJob::dispatch($payment);
                    $queued++;
                });
        });

        $this->info("Queued {$queued} payment check(s); expired {$expired} stale payment(s).");

        return self::SUCCESS;
    }
}
