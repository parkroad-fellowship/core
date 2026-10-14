<?php

namespace App\Listeners\Payment;

use App\Events\Payment\PaymentSucceeded;
use App\Jobs\Pledge\ReconcilePaymentJob;

/**
 * Records a successful payment against the payer's pledge straight away; the hourly
 * reconcile command remains as a safety net.
 */
class ReconcilePledgePayment
{
    public function handle(PaymentSucceeded $event): void
    {
        if ($event->payment->pledge_id === null) {
            ReconcilePaymentJob::dispatchSync($event->payment);
        }
    }
}
