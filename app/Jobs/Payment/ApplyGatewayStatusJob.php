<?php

namespace App\Jobs\Payment;

use App\Enums\PRFPaymentStatus;
use App\Events\Payment\PaymentSucceeded;
use App\Models\Payment;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

/**
 * The only place a payment's status changes after checkout starts. Used by the webhook, the
 * "check status" endpoint and the poller, so they can race safely:
 *
 * - the row is locked while it is updated;
 * - a final status is never downgraded (a late "failed" cannot undo a success);
 * - Paystack's in-progress states keep the payment open;
 * - "abandoned" only cancels once the checkout window has passed.
 */
class ApplyGatewayStatusJob
{
    use Dispatchable;

    public const CHECKOUT_WINDOW_MINUTES = 30;

    /**
     * @param  array<string, mixed>  $gatewayData  Paystack transaction `data`
     */
    public function __construct(
        public Payment $payment,
        public array $gatewayData,
    ) {}

    public function handle(): Payment
    {
        return DB::transaction(function (): Payment {
            $payment = Payment::query()->whereKey($this->payment->getKey())->lockForUpdate()->firstOrFail();

            $next = $this->statusFor($payment);

            if ($next === null || $next === $payment->payment_status) {
                return $payment;
            }

            $payment->update(['payment_status' => $next, 'transaction_meta' => $this->gatewayData]);

            if ($next === PRFPaymentStatus::SUCCESS) {
                PaymentSucceeded::dispatch($payment);
            }

            return $payment;
        });
    }

    private function statusFor(Payment $payment): ?PRFPaymentStatus
    {
        $gatewayStatus = $this->gatewayData['status'] ?? null;
        $current = $payment->payment_status;

        if (
            $current === PRFPaymentStatus::SUCCESS
            || $current === PRFPaymentStatus::EXPIRED && $gatewayStatus !== 'success'
        ) {
            return null;
        }

        return match ($gatewayStatus) {
            'success' => PRFPaymentStatus::SUCCESS,
            'failed' => $current->isTerminal() ? null : PRFPaymentStatus::FAILED,
            'reversed' => $current->isTerminal() ? null : PRFPaymentStatus::FAILED,
            'abandoned' => $payment->created_at?->lt(now()->subMinutes(self::CHECKOUT_WINDOW_MINUTES))
                && !$current->isTerminal()
                    ? PRFPaymentStatus::CANCELLED
                    : null,
            // ongoing, pending, processing, queued: still in progress.
            default => null,
        };
    }
}
