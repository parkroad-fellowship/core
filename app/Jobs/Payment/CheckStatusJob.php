<?php

namespace App\Jobs\Payment;

use App\Contracts\Services\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Asks Paystack for a payment's status and applies it. Synchronous: used by the webhook and
 * the "check status" endpoint. A failed lookup never changes the payment.
 */
class CheckStatusJob
{
    use Dispatchable;

    public function __construct(
        public Payment $payment,
    ) {}

    public function handle(PaymentGatewayInterface $gateway): Payment
    {
        $result = $gateway->verifyTransaction((string) $this->payment->reference);

        $this->payment->update([
            'status_checked_at' => now(),
            'status_check_count' => $this->payment->status_check_count + 1,
        ]);

        if (!$result['status'] || !is_array($result['data'] ?? null)) {
            return $this->payment;
        }

        return ApplyGatewayStatusJob::dispatchSync($this->payment, $result['data']);
    }
}
