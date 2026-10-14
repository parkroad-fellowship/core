<?php

namespace App\Listeners\Payment;

use App\Events\LedgerEntry\IncomeReceipted;
use App\Events\Payment\PaymentSucceeded;
use App\Services\Finance\Ledger;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Books a successful online gift in the treasurer's cashbook (gross gift + Paystack fee) and
 * receipts the giver. Idempotent: a replayed webhook posts nothing new.
 */
class PostPaymentToLedger implements ShouldQueue, ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly Ledger $ledger,
    ) {}

    public function viaQueue(): string
    {
        return 'high';
    }

    public function handle(PaymentSucceeded $event): void
    {
        $gift = $this->ledger->postPayment($event->payment)['gift'];

        if ($gift->wasRecentlyCreated) {
            IncomeReceipted::dispatch($gift, $gift->giver_email !== null);
        }
    }
}
