<?php

use App\Actions\Tenant\AddTenantMemberAction;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class, RefreshDatabase::class)
    ->beforeEach(function () {
        $tenant = Tenant::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());
    })->in('Feature');

uses(TestCase::class, RefreshDatabase::class)->beforeEach(function () {
    $this->withoutMiddleware(\App\Http\Middleware\VerifyRequestSignature::class);

    $tenant = Tenant::factory()->create();
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
})->in('Unit');

uses(TestCase::class)->in('Services');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of actions in your test files.
|
*/

function createOrGetTenant(): Tenant
{

    $tenant = Tenant::first();
    if (! $tenant) {
        $tenant = Tenant::factory()->create();
    }

    return $tenant;
}

function initTenancy(Tenant $tenant): void
{
    tenancy()->initialize($tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
}

function actingAsTenantUser(array $roles = ['super admin', 'member']): User
{
    $tenant = createOrGetTenant();

    initTenancy($tenant);

    (new \Database\Seeders\RolesAndPermissionsSeeder)->run();

    $user = User::factory()->create();
    $user->assignRole($roles);
    app(AddTenantMemberAction::class)->handle($tenant, $user, 'admin');

    test()->actingAs($user)->withHeaders(tenantHeaders($tenant));

    return $user;
}

function tenantHeaders(Tenant $tenant): array
{
    return ['X-Tenant' => $tenant->id];
}

function actingAsStaticUser(
    User $user,
) {
    $tenant = createOrGetTenant();

    return test()->actingAs($user)->withHeaders(tenantHeaders($tenant));
}

function actingAsUser()
{
    (new \Database\Seeders\RolesAndPermissionsSeeder)->run();

    $user = User::factory()->create();
    $user->assignRole('super admin');

    return test()->actingAs($user);
}
