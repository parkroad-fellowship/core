<?php

it('rejects paystack webhook with missing signature', function () {
    $this->postJson('/api/v1/paystack/ipn', [
        'event' => 'charge.success',
        'data' => ['reference' => 'test-ref'],
    ])->assertForbidden();
});

it('rejects paystack webhook with invalid signature', function () {
    $this->postJson('/api/v1/paystack/ipn', [
        'event' => 'charge.success',
        'data' => ['reference' => 'test-ref'],
    ], [
        'X-Paystack-Signature' => 'invalid-signature',
    ])->assertForbidden();
});

it('accepts paystack webhook with valid signature', function () {
    $payload = json_encode([
        'event' => 'charge.success',
        'data' => ['reference' => 'non-existent-ref'],
    ]);

    $secret = 'test-paystack-secret-key';
    config(['prf.payments.paystack.secret_key' => $secret]);

    $signature = hash_hmac('sha512', $payload, $secret);

    $response = $this->call('POST', '/api/v1/paystack/ipn', [], [], [], [
        'HTTP_X-Paystack-Signature' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    expect($response->status())->not->toBe(403);
});
