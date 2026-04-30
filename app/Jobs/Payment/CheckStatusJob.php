<?php

namespace App\Jobs\Payment;

use App\Enums\PRFPaymentStatus;
use App\Models\Payment;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;

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

        // Verify the payment status with Paystack
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('prf.payments.paystack.secret_key'),
        ])->get(config('prf.payments.paystack.base_url')."/transaction/verify/{$payment->reference}");

        $responseBody = $response->json();

        switch ($response->status()) {
            case 200:
                $payment->update([
                    'payment_status' => match ($responseBody['data']['status']) {
                        'success' => PRFPaymentStatus::SUCCESS,
                        'failed' => PRFPaymentStatus::FAILED,
                        'abandoned' => PRFPaymentStatus::CANCELLED,
                        default => PRFPaymentStatus::FAILED,
                    },
                    'transaction_meta' => $responseBody['data'],
                ]);
                break;
            default:
                $payment->update([
                    'payment_status' => PRFPaymentStatus::FAILED,
                ]);
                break;
        }

        $payment->refresh();
    }
}
