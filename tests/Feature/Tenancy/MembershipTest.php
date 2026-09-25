<?php

use App\Actions\Tenant\AddTenantMemberAction;
use App\Models\User;

beforeEach(function () {
    tenancy()->end();
});

it('allows multi-tenant user with separate tokens', function () {
    $user = User::factory()->create();
    $tenantA = createTenant();
    $tenantB = createTenant();
    app(AddTenantMemberAction::class)->handle($tenantA, $user, 'member');
    app(AddTenantMemberAction::class)->handle($tenantB, $user, 'member');

    initTenancy($tenantA);
    $tokenA = $user->createToken('a')->plainTextToken;

    initTenancy($tenantB);
    $tokenB = $user->createToken('b')->plainTextToken;

    $this
        ->withHeader('X-Tenant', $tenantA->id)
        ->withHeader('Authorization', "Bearer {$tokenA}")
        ->getJson(route('api.auth.me'))
        ->assertOk();

    app('auth')->forgetGuards();

    $this
        ->withHeader('X-Tenant', $tenantB->id)
        ->withHeader('Authorization', "Bearer {$tokenA}")
        ->getJson(route('api.auth.me'))
        ->assertStatus(401);

    app('auth')->forgetGuards();

    $this
        ->withHeader('X-Tenant', $tenantB->id)
        ->withHeader('Authorization', "Bearer {$tokenB}")
        ->getJson(route('api.auth.me'))
        ->assertOk();
});
