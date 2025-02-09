<?php

namespace App\Jobs\Payment;

use App\Enums\PRFPaymentStatus;
use App\Jobs\PesaPal\CheckTransactionStatusJob;
use App\Jobs\PesaPal\GetTokenJob;
use App\Models\Payment;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckStatusJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Payment $payment,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $payment = $this->payment;

        $accessToken = GetTokenJob::dispatchSync();
        $status = CheckTransactionStatusJob::dispatchSync(
            $accessToken,
            $payment->order_tracking_id,
        );

        $payment->update([
            'payment_status' => match ($status['status_code']) {
                1 => PRFPaymentStatus::SUCCESS,
                2 => PRFPaymentStatus::CANCELLED,
                default => PRFPaymentStatus::FAILED,
            },
            'transaction_meta' => $status,
        ]);

        $payment->refresh();
    }
}
