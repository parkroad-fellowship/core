<?php

use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

function tenantId(): string
{
    return Tenant::firstOrFail()->id;
}

function initTenant(): void
{
    $tenant = Tenant::firstOrFail();
    tenancy()->initialize($tenant);
}

it('should return a token when user is authenticated', function () {
    $password = 'password';

    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);
    $user->tenants()->attach(tenantId(), ['role' => 'member']);

    $response = postJson(
        route('api.auth.login'),
        [
            'email' => $user->email,
            'password' => $password,
        ],
        [
            'X-Tenant' => tenantId(),
        ],
    );

    $response->assertStatus(200);
    $result = $response->json();

    expect($result)->toHaveKeys([
        'token',
    ]);
});

it('should return the user details when a user provides a valid authentication token', function () {
    $password = 'password';

    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);
    $user->tenants()->attach(tenantId(), ['role' => 'member']);

    $response = postJson(
        route('api.auth.login'),
        [
            'email' => $user->email,
            'password' => $password,
        ],
        [
            'X-Tenant' => tenantId(),
        ],
    );

    $token = $response->json('token');

    $response = getJson(route('api.auth.me'), [
        'Authorization' => "Bearer $token",
        'X-Tenant' => tenantId(),
    ]);

    $response->assertStatus(200);
    $result = $response->json();

    expect($result)->toHaveKeys([
        'data' => [
            'ulid',
            'name',
            'email',
            'created_at',
            'updated_at',
        ],
    ]);
});

it('should log out a user when they provide their token', function () {
    $password = 'password';

    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);
    $user->tenants()->attach(tenantId(), ['role' => 'member']);

    $response = postJson(
        route('api.auth.login'),
        [
            'email' => $user->email,
            'password' => $password,
        ],
        [
            'X-Tenant' => tenantId(),
        ],
    );

    $token = $response->json('token');

    $response = postJson(
        route('api.auth.logout'),
        [],
        [
            'Authorization' => "Bearer $token",
            'X-Tenant' => tenantId(),
        ],
    );

    $response->assertStatus(200);
    $result = $response->json();

    expect($result)->toHaveKeys([
        'message',
    ]);

    expect($result['message'])->toBe('Logged out');
});

it('should return a user with requested relations', function () {
    initTenant();

    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
    $password = 'password';

    $user = User::factory()->has(Member::factory())->create([
        'password' => Hash::make($password),
    ]);
    $user->tenants()->attach(tenantId(), ['role' => 'member']);

    $response = postJson(
        route('api.auth.login'),
        [
            'email' => $user->email,
            'password' => $password,
        ],
        [
            'X-Tenant' => tenantId(),
        ],
    );

    $token = $response->json('token');

    $response = getJson(
        route('api.auth.me', [
            'include' => 'roles,roles.permissions,member',
        ]),
        [
            'Authorization' => "Bearer $token",
            'X-Tenant' => tenantId(),
        ],
    );

    $response->assertStatus(200);
    $result = $response->json();

    expect($result)->toHaveKeys([
        'data' => [
            'ulid',
            'name',
            'email',
            'created_at',
            'updated_at',
            'roles',
            'member',
        ],
    ]);
});

it('can sign up a member user', function () {
    initTenant();
    Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

    $password = 'Xk9mWq2vLp7nRs4t';
    $email = 'member-1-test@' . config('prf.app.org_email_domain', 'example.org');

    $response = postJson(
        route('api.auth.register'),
        [
            'name' => 'John Doe',
            'email' => $email,
            'password' => $password,
        ],
        [
            'X-Tenant' => tenantId(),
        ],
    );

    $response
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'ulid',
                'name',
                'email',
                'created_at',
                'updated_at',
            ],
        ]);

    $result = $response->json();
    expect($result['data']['email'])->toBe($email);
});

it('should return an existing user with requested relations', function () {
    initTenant();
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
    $password = 'password';

    $user = User::factory()->has(Member::factory())->create([
        'password' => Hash::make($password),
    ]);
    $user->tenants()->attach(tenantId(), ['role' => 'member']);

    $response = postJson(
        route('api.auth.login'),
        [
            'email' => $user->email,
            'password' => $password,
        ],
        [
            'X-Tenant' => tenantId(),
        ],
    );

    $token = $response->json('token');

    $response = getJson(
        route('api.auth.me', [
            'include' => 'roles,roles.permissions,member,member.groupMembers',
        ]),
        [
            'Authorization' => "Bearer $token",
            'X-Tenant' => tenantId(),
        ],
    );

    $response->assertStatus(200);
    $result = $response->json();

    expect($result)->toHaveKeys([
        'data' => [
            'ulid',
            'name',
            'email',
            'created_at',
            'updated_at',
            'member' => [
                'group_members',
            ],
        ],
    ]);
});

it('can sign up a student user and issue random account details', function () {
    initTenant();
    Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

    $response = postJson(
        route('api.auth.register-student'),
        [],
        [
            'X-Tenant' => tenantId(),
        ],
    );

    $response
        ->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'ulid',
                'name',
                'email',
                'password',
                'created_at',
                'updated_at',
            ],
        ]);
});
