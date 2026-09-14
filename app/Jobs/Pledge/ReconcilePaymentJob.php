<?php

namespace App\Jobs\Pledge;

use App\Enums\PRFPledgeInstallmentMethod;
use App\Models\Payment;
use App\Models\Pledge;
use App\Models\PledgeInstallment;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class ReconcilePaymentJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Payment $payment,
    ) {}

    /**
     * Attempt to link a processed payment to a matching pledge. Returns the
     * created installment, or null when no safe match can be found (the
     * Treasurer can still reconcile the payment manually in Filament).
     */
    public function handle(): ?PledgeInstallment
    {
        $pledge = $this->findMatchingPledge($this->payment);

        if (!$pledge) {
            return null;
        }

        $installment = PledgeInstallment::create([
            'pledge_id' => $pledge->id,
            'amount' => $this->payment->amount / 100,
            'fulfilled_on' => Carbon::today(),
            'method' => PRFPledgeInstallmentMethod::PAYSTACK->value,
            'payment_id' => $this->payment->id,
        ]);

        $this->payment->update(['pledge_id' => $pledge->id]);

        return $installment;
    }

    private function findMatchingPledge(Payment $payment): ?Pledge
    {
        // 1. Existing app member link.
        if ($payment->member_id) {
            $byMember = Pledge::query()->where('member_id', $payment->member_id)->orderByDesc('created_at')->first();

            if ($byMember) {
                return $byMember;
            }
        }

        // 2. Payer email carried on the Paystack transaction.
        $email = Arr::get($payment->transaction_meta ?? [], 'customer.email') ?? Arr::get(
            $payment->transaction_meta ?? [],
            'email',
        );

        if (filled($email)) {
            $byEmail = Pledge::query()->where('email', $email)->orderByDesc('created_at')->first();

            if ($byEmail) {
                return $byEmail;
            }
        }

        return null;
    }
}
