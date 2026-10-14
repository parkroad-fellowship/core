<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lets someone already registered elsewhere join with their existing password', function () {
    $user = User::factory()->create(['email' => 'shared@gmail.com', 'password' => Hash::make('Secret-pass-123')]);

    $this->postJson(
        route('api.auth.register'),
        [
            'name' => 'Shared Person',
            'email' => 'shared@gmail.com',
            'password' => 'Secret-pass-123',
        ],
        tenantHeaders(tenant()),
    )->assertSuccessful();

    expect($user->fresh()->belongsToTenant(tenant('id')))->toBeTrue();
});

it('never attaches someone else\'s account without their password', function () {
    $user = User::factory()->create(['email' => 'victim@gmail.com', 'password' => Hash::make('Secret-pass-123')]);

    $this->postJson(
        route('api.auth.register'),
        [
            'name' => 'Attacker',
            'email' => 'victim@gmail.com',
            'password' => 'Different-pass-456',
        ],
        tenantHeaders(tenant()),
    )->assertUnprocessable();

    expect($user->fresh()->belongsToTenant(tenant('id')))->toBeFalse();
});
