<?php

namespace App\Jobs\Payment;

use App\Enums\PRFPaymentStatus;
use App\Jobs\PayStack\InitialiseTransactionJob;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Foundation\Bus\Dispatchable;
use Throwable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): Payment
    {
        $data = $this->data;

        $member = Member::where('ulid', $data['member_ulid'])->firstOrFail();
        $paymentType = PaymentType::where('ulid', $data['payment_type_ulid'])->firstOrFail();

        $payment = Payment::create([
            'member_id' => $member->id,
            'payment_type_id' => $paymentType->id,
            'amount' => $data['amount'],
        ]);

        // Ensure amount is valid without a decimal point
        $amount = intval($payment->amount) * 100; // Convert to kobo

        try {
            // Returns Paystack's transaction `data` (reference, access_code, authorization_url).
            $transaction = InitialiseTransactionJob::dispatchSync([
                'amount' => $amount,
                'email' => $member->email,
                'id' => $payment->ulid,
            ]);
        } catch (Throwable $exception) {
            $payment->update(['payment_status' => PRFPaymentStatus::FAILED]);

            throw $exception;
        }

        $payment->update([
            'reference' => $transaction['reference'],
            'access_code' => $transaction['access_code'],
            'authorization_url' => $transaction['authorization_url'],
            'payment_status' => PRFPaymentStatus::INITIALISED,
            'next_status_check_at' => now()->addMinutes(VerifyStatusJob::nextDelay(0)),
        ]);

        $payment->refresh();

        return $payment;
    }
}
