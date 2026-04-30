<?php

it('rate limits auth endpoints after exceeding limit', function () {
    $maxAttemptsPerMinute = 60;

    for ($i = 0; $i < $maxAttemptsPerMinute; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ])->assertStatus(429);
});

it('segments auth rate limits by cloudflare connecting ip', function () {
    $maxAttemptsPerMinute = 60;

    for ($i = 0; $i < $maxAttemptsPerMinute; $i++) {
        $this->withHeaders([
            'CF-Connecting-IP' => '198.51.100.10',
        ])->postJson('/api/v1/auth/login', [
            'email' => 'edge-a@example.com',
            'password' => 'password',
        ]);
    }

    $this->withHeaders([
        'CF-Connecting-IP' => '198.51.100.10',
    ])->postJson('/api/v1/auth/login', [
        'email' => 'edge-a@example.com',
        'password' => 'password',
    ])->assertStatus(429);

    $differentClientIpResponse = $this->withHeaders([
        'CF-Connecting-IP' => '198.51.100.11',
    ])->postJson('/api/v1/auth/login', [
        'email' => 'edge-a@example.com',
        'password' => 'password',
    ]);

    expect($differentClientIpResponse->status())->not->toBe(429);
});
