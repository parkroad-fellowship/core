<?php

namespace App\Jobs\Payment;

use App\Contracts\Services\PaymentGatewayInterface;
use App\Enums\PRFPaymentStatus;
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
    public function handle(PaymentGatewayInterface $payment): void
    {
        $result = $payment->verifyTransaction($this->payment->reference);

        if ($result['status']) {
            $this->payment->update([
                'payment_status' => match ($result['data']['status'] ?? '') {
                    'success' => PRFPaymentStatus::SUCCESS,
                    'failed' => PRFPaymentStatus::FAILED,
                    'abandoned' => PRFPaymentStatus::CANCELLED,
                    default => PRFPaymentStatus::FAILED,
                },
                'transaction_meta' => $result['data'],
            ]);
        } else {
            $this->payment->update([
                'payment_status' => PRFPaymentStatus::FAILED,
            ]);
        }

        $this->payment->refresh();
    }
}
