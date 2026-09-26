<?php

beforeEach(function () {
    $this->withHeaders(tenantHeaders(tenant()));
});

it('rate limits auth endpoints after exceeding limit', function () {
    $maxAttemptsPerMinute = 200;

    for ($i = 0; $i < $maxAttemptsPerMinute; $i++) {
        $this->postJson(route('api.auth.login'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
    }

    $this->postJson(route('api.auth.login'), [
        'email' => 'test@example.com',
        'password' => 'password',
    ])->assertStatus(429);
});

it('segments auth rate limits by cloudflare connecting ip', function () {
    $maxAttemptsPerMinute = 200;

    for ($i = 0; $i < $maxAttemptsPerMinute; $i++) {
        $this->withHeaders([
            'CF-Connecting-IP' => '198.51.100.10',
        ])->postJson(route('api.auth.login'), [
            'email' => 'edge-a@example.com',
            'password' => 'password',
        ]);
    }

    $this
        ->withHeaders([
            'CF-Connecting-IP' => '198.51.100.10',
        ])
        ->postJson(route('api.auth.login'), [
            'email' => 'edge-a@example.com',
            'password' => 'password',
        ])
        ->assertStatus(429);

    $differentClientIpResponse = $this->withHeaders([
        'CF-Connecting-IP' => '198.51.100.11',
    ])->postJson(route('api.auth.login'), [
        'email' => 'edge-a@example.com',
        'password' => 'password',
    ]);

    expect($differentClientIpResponse->status())->not->toBe(429);
});
