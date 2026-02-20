<?php

it('rejects africas talking webhook with missing secret', function () {
    config(['prf.app.africas_talking.webhook_secret' => 'test-webhook-secret']);

    $this->postJson('/api/v1/communications/africa-is-talking/entrypoint')
        ->assertForbidden();
});

it('rejects africas talking webhook with invalid secret', function () {
    config(['prf.app.africas_talking.webhook_secret' => 'test-webhook-secret']);

    $this->postJson('/api/v1/communications/africa-is-talking/entrypoint', [], [
        'X-Webhook-Secret' => 'wrong-secret',
    ])->assertForbidden();
});

it('rejects africas talking webhook when secret is not configured', function () {
    config(['prf.app.africas_talking.webhook_secret' => null]);

    $this->postJson('/api/v1/communications/africa-is-talking/entrypoint', [], [
        'X-Webhook-Secret' => 'anything',
    ])->assertForbidden();
});

it('accepts africas talking webhook with valid secret', function () {
    config(['prf.app.africas_talking.webhook_secret' => 'test-webhook-secret']);

    $response = $this->postJson('/api/v1/communications/africa-is-talking/entrypoint', [], [
        'X-Webhook-Secret' => 'test-webhook-secret',
    ]);

    expect($response->status())->not->toBe(403);
});
