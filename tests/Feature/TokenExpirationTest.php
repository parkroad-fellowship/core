<?php

use App\Models\User;

it('rejects expired sanctum tokens', function () {
    config(['sanctum.expiration' => 60]);

    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $this->travel(61)->minutes();

    $this->getJson('/api/v1/auth/me', [
        'Authorization' => 'Bearer '.$token,
    ])->assertUnauthorized();
});

it('accepts valid sanctum tokens', function () {
    config(['sanctum.expiration' => 60]);

    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $this->getJson('/api/v1/auth/me', [
        'Authorization' => 'Bearer '.$token,
    ])->assertSuccessful();
});
