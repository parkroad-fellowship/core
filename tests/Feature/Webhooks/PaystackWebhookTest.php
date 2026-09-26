<?php

use App\Enums\PRFPaymentStatus;
use App\Models\Payment;
use App\Models\Tenant;
use Database\Seeders\GroupSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Http;

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

function paystackWebhook(string $tenantId, array $payload, string $secret): \Illuminate\Testing\TestResponse
{
    $body = json_encode($payload);

    return test()->call(
        'POST',
        route('api.paystack.notifyPayment', ['tenant' => $tenantId]),
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, $secret),
        ],
        content: $body,
    );
}

it('confirms a payment when the tenant\'s own secret signed the webhook', function () {
    $payment = Payment::factory()->create([
        'reference' => 'ref-123',
        'payment_status' => PRFPaymentStatus::INITIALISED,
    ]);
    Http::fake(['api.paystack.co/transaction/verify/ref-123' => Http::response([
        'status' => true,
        'data' => ['status' => 'success', 'reference' => 'ref-123'],
    ])]);

    paystackWebhook(
        tenant('id'),
        ['event' => 'charge.success', 'data' => ['reference' => 'ref-123']],
        'sk_test_tenant',
    )->assertOk();

    expect($payment->fresh()->payment_status)->toBe(PRFPaymentStatus::SUCCESS);
    Http::assertSent(fn($request) => $request->hasHeader('Authorization', 'Bearer sk_test_tenant'));
});

it('rejects a webhook signed with another tenant\'s secret', function () {
    paystackWebhook(
        tenant('id'),
        ['event' => 'charge.success', 'data' => ['reference' => 'ref-123']],
        'sk_other_tenant',
    )->assertForbidden();
});

it('rejects a webhook for a tenant that has not configured Paystack', function () {
    $unconfigured = Tenant::factory()->create();

    paystackWebhook(
        $unconfigured->id,
        ['event' => 'charge.success', 'data' => ['reference' => 'ref-123']],
        'sk_test_tenant',
    )->assertForbidden();
});

it('returns not found for an unknown tenant', function () {
    paystackWebhook('not-a-tenant', ['event' => 'charge.success'], 'sk_test_tenant')->assertNotFound();
});

it('acknowledges events it does not handle', function () {
    paystackWebhook(tenant('id'), ['event' => 'transfer.success'], 'sk_test_tenant')
        ->assertOk()
        ->assertJsonPath('message', 'Event ignored.');
});
