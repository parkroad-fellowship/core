<?php

use App\Actions\Tenant\AddTenantMemberAction;
use App\Models\User;

beforeEach(function () {
    $this->withHeaders(tenantHeaders(tenant()));
});

it('rejects expired sanctum tokens', function () {
    config(['sanctum.expiration' => 60]);

    $user = User::factory()->create();
    app(AddTenantMemberAction::class)->handle(tenant(), $user, 'member');
    $token = $user->createToken('test-token')->plainTextToken;

    $this->travel(61)->minutes();

    $this->getJson(route('api.auth.me'), [
        'Authorization' => 'Bearer ' . $token,
    ])->assertUnauthorized();
});

it('accepts valid sanctum tokens', function () {
    config(['sanctum.expiration' => 60]);

    $user = User::factory()->create();
    app(AddTenantMemberAction::class)->handle(tenant(), $user, 'member');
    $token = $user->createToken('test-token')->plainTextToken;

    $this->getJson(route('api.auth.me'), [
        'Authorization' => 'Bearer ' . $token,
    ])->assertSuccessful();
});
