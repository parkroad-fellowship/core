<?php

use App\Enums\PRFPaymentStatus;
use App\Jobs\Pledge\ReconcilePaymentJob;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Pledge;
use Database\Seeders\GroupSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, GroupSeeder::class]);
});

it('records the installment in the same KES amount as the payment', function () {
    $member = Member::factory()->create();
    Pledge::factory()->create(['member_id' => $member->id, 'amount' => 10_000]);
    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'amount' => 2_500,
        'payment_status' => PRFPaymentStatus::SUCCESS,
    ]);

    $installment = ReconcilePaymentJob::dispatchSync($payment);

    expect((int) $installment->amount)->toBe(2_500);
});

it('reconciles every unmatched successful payment', function () {
    $member = Member::factory()->create();
    Pledge::factory()->create(['member_id' => $member->id]);
    Payment::factory()
        ->count(30)
        ->create([
            'member_id' => $member->id,
            'payment_status' => PRFPaymentStatus::SUCCESS,
            'pledge_id' => null,
        ]);

    $this
        ->artisan('prf:pledges:reconcile-payments')
        ->expectsOutput('Reconciled 30 payment(s) to pledges.')
        ->assertSuccessful();

    expect(Payment::query()->whereNull('pledge_id')->count())->toBe(0);
});
