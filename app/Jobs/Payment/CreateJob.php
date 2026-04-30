<?php

namespace App\Jobs\Payment;

use App\Enums\PRFPaymentStatus;
use App\Jobs\PayStack\InitialiseTransactionJob;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {
        //
    }

    /**
     * Execute the job.
     */
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

        $transaction = InitialiseTransactionJob::dispatchSync(
            [
                'amount' => $amount,
                'email' => $member->email,
                'id' => $payment->ulid,
            ],
        );

        $payment->update([
            'reference' => $transaction['data']['reference'],
            'access_code' => $transaction['data']['access_code'],
            'authorization_url' => $transaction['data']['authorization_url'],
            'payment_status' => match ($transaction['status']) {
                true => PRFPaymentStatus::INITIALISED,
                default => PRFPaymentStatus::FAILED,
            },
        ]);
        $payment->refresh();

        return $payment;
    }
}
