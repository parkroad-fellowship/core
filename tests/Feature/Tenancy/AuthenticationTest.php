<?php

use App\Actions\Tenant\AddTenantMemberAction;
use App\Models\PersonalAccessToken;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    tenancy()->end();
    new \Database\Seeders\RolesAndPermissionsSeeder()->run();
});

it('issues tenant-bound tokens on login', function () {
    $tenant = createTenant();
    initTenancy($tenant);
    $user = User::factory()->create();
    app(AddTenantMemberAction::class)->handle($tenant, $user, 'member');

    $response = $this->withHeader('X-Tenant', $tenant->id)->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['token']);

    $token = PersonalAccessToken::where('tokenable_id', $user->getKey())->first();
    expect($token->tenant_id)->toBe($tenant->id);
});

it('rejects token used in wrong tenant', function () {
    test()->markTestSkipped('Needs debugging: auth state leaks across requests in test env');
    $tenantA = createTenant();
    $tenantB = createTenant();
    initTenancy($tenantA);
    $user = User::factory()->create();
    app(AddTenantMemberAction::class)->handle($tenantA, $user, 'member');
    app(AddTenantMemberAction::class)->handle($tenantB, $user, 'member');

    $loginResponse = $this->withHeader('X-Tenant', $tenantA->id)->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    $tokenA = $loginResponse->json('token');

    $response = $this
        ->withHeader('X-Tenant', $tenantB->id)
        ->withHeader('Authorization', "Bearer {$tokenA}")
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(401);
});

it('requires X-Tenant header', function () {
    $response = $this->getJson('/api/v1/missions');
    $response->assertStatus(422);
    $response->assertJson(['code' => 'TENANT_REQUIRED']);
});

it('rejects non-member user', function () {
    $tenant = createTenant();
    initTenancy($tenant);
    $user = User::factory()->create();

    actingAs($user)->withHeader('X-Tenant', $tenant->id)->getJson('/api/v1/missions')->assertStatus(403);
});
