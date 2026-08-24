<?php

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

it('persists the tenant id when roles are synced through the roles relationship', function () {
    $tenant = Tenant::factory()->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());

    $role = Role::create([
        'name' => 'member',
        'guard_name' => 'web',
        'tenant_id' => $tenant->getKey(),
    ]);

    $user = User::factory()->create();

    // Simulate Filament Select->relationship()->sync().
    $user->roles()->sync([$role->id], detaching: false);

    expect(
        DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('model_type', User::class)
            ->where('role_id', $role->id)
            ->value('tenant_id'),
    )
        ->toBe($tenant->getKey());
});

it('allows a role to be removed and re-added in the same tenant', function () {
    $tenant = Tenant::factory()->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());

    $role = Role::create([
        'name' => 'member',
        'guard_name' => 'web',
        'tenant_id' => $tenant->getKey(),
    ]);

    $user = User::factory()->create();
    $user->assignRole($role);

    $user->roles()->detach([$role->id]);
    $user->roles()->sync([$role->id], detaching: false);

    expect(fn() => $user->roles()->sync([$role->id], detaching: false))
        ->not
        ->toThrow(UniqueConstraintViolationException::class);

    $pivots = DB::table('model_has_roles')->where('model_id', $user->id)->where('model_type', User::class)->get();

    expect($pivots)->toHaveCount(1);
    expect($pivots->first()->tenant_id)->toBe($tenant->getKey());
});

it('allows the same user to hold the same role in different tenants', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $role = Role::create([
        'name' => 'super admin',
        'guard_name' => 'web',
        'tenant_id' => null,
    ]);

    $user = User::factory()->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenantA->getKey());
    $user->roles()->sync([$role->id], detaching: false);

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenantB->getKey());
    expect(fn() => $user->roles()->sync([$role->id], detaching: false))
        ->not
        ->toThrow(UniqueConstraintViolationException::class);

    expect(DB::table('model_has_roles')->where('model_id', $user->id)->where('model_type', User::class)->count())
        ->toBe(2);
});

it('scopes role reads to the current tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $roleA = Role::create([
        'name' => 'member',
        'guard_name' => 'web',
        'tenant_id' => $tenantA->getKey(),
    ]);

    $roleB = Role::create([
        'name' => 'member',
        'guard_name' => 'web',
        'tenant_id' => $tenantB->getKey(),
    ]);

    $user = User::factory()->create();

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenantA->getKey());
    $user->roles()->sync([$roleA->id], detaching: false);

    expect($user->roles()->pluck('roles.id'))->toContain($roleA->id);
    expect($user->roles()->pluck('roles.id'))->not->toContain($roleB->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenantB->getKey());
    $user->roles()->sync([$roleB->id], detaching: false);

    expect($user->roles()->pluck('roles.id'))->toContain($roleB->id);
    expect($user->roles()->pluck('roles.id'))->not->toContain($roleA->id);
});
