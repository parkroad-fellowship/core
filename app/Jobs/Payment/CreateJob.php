<?php

namespace App\Jobs\Payment;

use App\Jobs\PesaPal\GetTokenJob;
use App\Jobs\PesaPal\SubmitOrderJob;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Str;

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

        $accessToken = GetTokenJob::dispatchSync();
        $order = SubmitOrderJob::dispatchSync(
            $accessToken,
            [
                'id' => $payment->ulid,
                'amount' => $payment->amount,
                'description' => "Given by {$member->first_name} {$member->last_name} for {$paymentType->name}",
                'phone_number' => Str::of($member->phone_number)
                    // Replace +254 with 0
                    ->replaceMatches('/^(\+254)/', '0')
                    ->__toString(),
                'email' => $member->email,
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
            ],
        );

        $payment->update([
            'redirect_url' => $order['redirect_url'],
            'order_tracking_id' => $order['order_tracking_id'],
            'order_meta' => $order,
        ]);
        $payment->refresh();

        return $payment;
    }
}
