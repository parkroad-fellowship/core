<?php

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('should return a token when user is authenticated', function () {
    // Set up
    $password = 'password';

    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    // Act
    $response = postJson(route('api.auth.login'), [
        'email' => $user->email,
        'password' => $password,
    ]);

    // Assert
    $response->assertStatus(200);
    $result = $response->json();

    expect($result)
        ->toHaveKeys([
            'token',
        ]);
});

it('should return the user details when a user provides a valid authentication token', function () {
    // Set up
    $password = 'password';

    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    $response = postJson(route('api.auth.login'), [
        'email' => $user->email,
        'password' => $password,
    ]);

    $token = $response->json('token');

    // Act
    $response = getJson(route('api.auth.me'), [
        'Authorization' => "Bearer $token",
    ]);

    // Assert
    $response->assertStatus(200);
    $result = $response->json();

    expect($result)
        ->toHaveKeys([
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
    // Set up
    $password = 'password';

    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    $response = postJson(route('api.auth.login'), [
        'email' => $user->email,
        'password' => $password,
    ]);

    $token = $response->json('token');

    // Act
    $response = postJson(route('api.auth.logout'), [], [
        'Authorization' => "Bearer $token",
    ]);

    // Assert
    $response->assertStatus(200);
    $result = $response->json();

    expect($result)
        ->toHaveKeys([
            'message',
        ]);

    expect($result['message'])
        ->toBe('Logged out');

    // // Act - Ensure the token is no longer valid
    // $response = getJson('/api/v1/auth/me', [
    //     'Authorization' => "Bearer $token",
    // ]);

    // // Assert
    // $response->assertStatus(401);
});

it('should return a user with requested relations', function () {
    // Set up
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
    $password = 'password';

    $user = User::factory()
        ->has(Member::factory())
        ->create([
            'password' => Hash::make($password),
        ]);

    $response = postJson(route('api.auth.login'), [
        'email' => $user->email,
        'password' => $password,
    ]);

    $token = $response->json('token');

    // Act
    $response = getJson(route('api.auth.me', [
        'include' => 'roles,roles.permissions,member',
    ]), [
        'Authorization' => "Bearer $token",
    ]);

    // Assert
    $response->assertStatus(200);
    $result = $response->json();

    expect($result)
        ->toHaveKeys([
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
    // Set up
    Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

    $password = 'Xk9mWq2vLp7nRs4t';
    $email = 'member-1-test@'.config('prf.app.org_email_domain', 'example.org');

    // Act
    $response = postJson(route('api.auth.register'), [
        'name' => 'John Doe',
        'email' => $email,
        'password' => $password,
    ]);

    // Assert
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
    // Set up
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
    $password = 'password';

    $user = User::factory()
        ->has(Member::factory())
        ->create([
            'password' => Hash::make($password),
        ]);

    $response = postJson(route('api.auth.login'), [
        'email' => $user->email,
        'password' => $password,
    ]);

    $token = $response->json('token');

    // Act
    $response = getJson(route('api.auth.me', [
        'include' => 'roles,roles.permissions,member,member.groupMembers',
    ]), [
        'Authorization' => "Bearer $token",
    ]);

    // Assert
    $response->assertStatus(200);
    $result = $response->json();

    expect($result)
        ->toHaveKeys([
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
    // Set up
    Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

    // Act
    $response = postJson(route('api.auth.register-student'), []);

    // Assert
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
