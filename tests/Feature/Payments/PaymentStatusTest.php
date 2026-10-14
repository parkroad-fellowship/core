<?php

use App\Enums\PRFPaymentStatus;
use App\Events\Payment\PaymentSucceeded;
use App\Jobs\Payment\ApplyGatewayStatusJob;
use App\Jobs\Payment\CreateJob;
use App\Jobs\Payment\VerifyStatusJob;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Pledge;
use Database\Seeders\GroupSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, GroupSeeder::class]);
    // A successful gift emails the giver a PDF receipt; don't call the real renderer.
    fakePDFRendering();

    configureIntegration([
        'payments.paystack_secret_key' => 'sk_test_tenant',
        'payments.paystack_public_key' => 'pk_test_tenant',
        'payments.paystack_callback_url' => 'https://tenant.test/payments/done',
    ]);
});

function openPayment(array $attributes = []): Payment
{
    return Payment::factory()->create([
        'payment_status' => PRFPaymentStatus::INITIALISED,
        'reference' => 'ref-' . fake()->unique()->numberBetween(1, 99999),
        ...$attributes,
    ]);
}

describe('creating a payment', function () {
    it('initialises the checkout with the tenant\'s Paystack account and schedules the first check', function () {
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => [
                'reference' => 'ref-new',
                'access_code' => 'ac',
                'authorization_url' => 'https://checkout.paystack.com/x',
            ],
        ])]);

        $payment = CreateJob::dispatchSync([
            'member_ulid' => Member::factory()->create()->ulid,
            'payment_type_ulid' => PaymentType::factory()->create()->ulid,
            'amount' => 500,
        ]);

        expect($payment->fresh())
            ->payment_status->toBe(PRFPaymentStatus::INITIALISED)
            ->reference->toBe('ref-new')
            ->next_status_check_at->not->toBeNull();

        Http::assertSent(
            fn($request) => $request['amount'] === 50_000
            && $request['currency'] === 'KES'
            && $request->hasHeader('Authorization', 'Bearer sk_test_tenant'),
        );
    });

    it('marks the payment failed when Paystack refuses the checkout', function () {
        Http::fake(['api.paystack.co/*' => Http::response(['status' => false], 400)]);

        expect(fn() => CreateJob::dispatchSync([
            'member_ulid' => Member::factory()->create()->ulid,
            'payment_type_ulid' => PaymentType::factory()->create()->ulid,
            'amount' => 500,
        ]))
            ->toThrow(Exception::class);

        expect(Payment::query()->sole()->payment_status)->toBe(PRFPaymentStatus::FAILED);
    });
});

describe('applying gateway status', function () {
    it('confirms a successful payment and announces it', function () {
        Event::fake([PaymentSucceeded::class]);
        $payment = openPayment();

        ApplyGatewayStatusJob::dispatchSync($payment, ['status' => 'success']);

        expect($payment->fresh()->payment_status)->toBe(PRFPaymentStatus::SUCCESS);
        Event::assertDispatched(PaymentSucceeded::class);
    });

    it('never downgrades a successful payment', function () {
        $payment = openPayment(['payment_status' => PRFPaymentStatus::SUCCESS]);

        ApplyGatewayStatusJob::dispatchSync($payment, ['status' => 'failed']);

        expect($payment->fresh()->payment_status)->toBe(PRFPaymentStatus::SUCCESS);
    });

    it('keeps payments that are still in progress open', function (string $gatewayStatus) {
        $payment = openPayment();

        ApplyGatewayStatusJob::dispatchSync($payment, ['status' => $gatewayStatus]);

        expect($payment->fresh()->payment_status)->toBe(PRFPaymentStatus::INITIALISED);
    })->with(['ongoing', 'pending', 'processing', 'queued']);

    it('only cancels an abandoned checkout after the checkout window', function () {
        $fresh = openPayment();
        $old = openPayment(['created_at' => now()->subMinutes(ApplyGatewayStatusJob::CHECKOUT_WINDOW_MINUTES + 1)]);

        ApplyGatewayStatusJob::dispatchSync($fresh, ['status' => 'abandoned']);
        ApplyGatewayStatusJob::dispatchSync($old, ['status' => 'abandoned']);

        expect($fresh->fresh()->payment_status)
            ->toBe(PRFPaymentStatus::INITIALISED)
            ->and($old->fresh()->payment_status)
            ->toBe(PRFPaymentStatus::CANCELLED);
    });

    it('records a successful payment against the payer\'s pledge', function () {
        $member = Member::factory()->create();
        $pledge = Pledge::factory()->create(['member_id' => $member->id]);
        $payment = openPayment(['member_id' => $member->id, 'amount' => 1_000]);

        ApplyGatewayStatusJob::dispatchSync($payment, ['status' => 'success']);

        expect($payment->fresh()->pledge_id)->toBe($pledge->id);
    });
});

describe('polling', function () {
    it('only queues payments that are due and expires stale ones', function () {
        Queue::fake();
        $due = openPayment(['next_status_check_at' => now()->subMinute()]);
        $notYetDue = openPayment(['next_status_check_at' => now()->addMinutes(10)]);
        $stale = openPayment(['created_at' => now()->subDays(2)]);

        $this->artisan('prf:payments:poll-status')->assertSuccessful();

        Queue::assertPushed(VerifyStatusJob::class, 1);
        Queue::assertPushed(VerifyStatusJob::class, fn(VerifyStatusJob $job) => $job->payment->is($due));
        expect($stale->fresh()->payment_status)
            ->toBe(PRFPaymentStatus::EXPIRED)
            ->and($notYetDue->fresh()->payment_status)
            ->toBe(PRFPaymentStatus::INITIALISED);
    });

    it('waits longer between each check', function () {
        expect(collect(range(0, 6))->map(fn(int $checks) => VerifyStatusJob::nextDelay($checks))->all())->toBe([
            2,
            5,
            10,
            30,
            60,
            60,
            60,
        ]);
    });

    it('schedules the next check when the payment is still open', function () {
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => ['status' => 'ongoing'],
        ])]);
        $payment = openPayment();

        new VerifyStatusJob($payment)->handle();

        expect($payment->fresh())
            ->status_check_count->toBe(1)
            ->next_status_check_at->toBeGreaterThan(now()->addMinutes(4));
    });
});
