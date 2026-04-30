<?php

it('rate limits auth endpoints after exceeding limit', function () {
    for ($i = 0; $i < 10; $i++) {
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
