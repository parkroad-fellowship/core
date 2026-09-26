<?php

use App\Actions\Tenant\AddTenantMemberAction;
use App\Enums\PRFMemberEmailMode;
use App\Http\Middleware\VerifyRequestSignature;
use App\Models\AppSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantIntegrations;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TenantReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;
use Tests\Fakes\FakeWorkspaceDirectory;
use Tests\TestCase;

/*
 | Feature and Unit tests each run inside a fresh tenant. Request signing is switched off because
 | the seeders create API clients; tests/Feature/RequestSignatureTest switches it back on.
 */
uses(TestCase::class, RefreshDatabase::class)->beforeEach(function () {
    $this->withoutMiddleware(VerifyRequestSignature::class);
    // The test image has no built front-end assets (the panel theme is built on deploy).
    $this->withoutVite();

    initTenancy(Tenant::factory()->create());
})->in('Feature', 'Unit');

uses(TestCase::class)->in('Services');

// Finance tests work inside a provisioned tenant: roles plus the chart of accounts.
pest()->beforeEach(function () {
    new RolesAndPermissionsSeeder()->run();
    new TenantReferenceDataSeeder()->run();
})->in('Feature/Finance');

function createOrGetTenant(): Tenant
{
    $tenant = Tenant::first();
    if (!$tenant) {
        $tenant = Tenant::factory()->create();
    }

    return $tenant;
}

function initTenancy(Tenant $tenant): void
{
    tenancy()->initialize($tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
}

/**
 * Store the current tenant's integration settings (as an admin would in App Settings)
 * and load them into config().
 *
 * @param  array<string, string>  $settings
 */
function configureIntegration(array $settings): void
{
    foreach ($settings as $key => $value) {
        AppSetting::set($key, $value);
    }

    app(TenantIntegrations::class)->load();
}

/**
 * Members of the current tenant sign in with their personal email.
 */
function usePersonalEmail(): void
{
    AppSetting::set('organization.member_email_mode', PRFMemberEmailMode::PERSONAL->value, 'organization', 'integer');
}

/**
 * Members of the current tenant get mailboxes on $domain, created in a fake Google Workspace.
 */
function useOrganisationDomain(string $domain = 'fellowship.org', bool $withWorkspace = true): FakeWorkspaceDirectory
{
    AppSetting::set(
        'organization.member_email_mode',
        PRFMemberEmailMode::ORGANISATION_DOMAIN->value,
        'organization',
        'integer',
    );
    AppSetting::set('organization.org_email_domain', $domain, 'organization');

    if ($withWorkspace) {
        configureIntegration([
            'google_workspace.service_account_json' => '{"type":"service_account"}',
            'google_workspace.admin_subject' => "admin@{$domain}",
        ]);
    }

    return FakeWorkspaceDirectory::install();
}

/**
 * PDFs are rendered by Gotenberg over HTTP; answer with a stub PDF so views still render.
 */
function fakePDFRendering(): void
{
    Http::fake(['*/forms/chromium/convert/*' => Http::response('%PDF-1.4 fake', 200, [
        'Content-Type' => 'application/pdf',
    ])]);
}

function createTenant(): Tenant
{
    return Tenant::factory()->create();
}

/**
 * A user who belongs to $tenant with the given roles. Leaves $tenant initialised.
 *
 * @param  list<string>  $roles
 */
function tenantUser(Tenant $tenant, array $roles = ['super admin', 'member']): User
{
    initTenancy($tenant);

    new \Database\Seeders\RolesAndPermissionsSeeder()->run();

    $user = User::factory()->create();
    $user->assignRole($roles);
    app(AddTenantMemberAction::class)->handle($tenant, $user, 'admin');

    return $user;
}

/**
 * @param  list<string>  $roles
 */
function actingAsTenantUser(array $roles = ['super admin', 'member']): TestCase
{
    $tenant = createOrGetTenant();

    return test()->actingAs(tenantUser($tenant, $roles))->withHeaders(tenantHeaders($tenant));
}

function tenantHeaders(Tenant $tenant): array
{
    return ['X-Tenant' => $tenant->id];
}

function actingAsStaticUser(User $user, array $roles = ['super admin', 'member'])
{
    $tenant = createOrGetTenant();
    initTenancy($tenant);

    new \Database\Seeders\RolesAndPermissionsSeeder()->run();
    $user->assignRole($roles);
    app(AddTenantMemberAction::class)->handle($tenant, $user, 'admin');

    return test()->actingAs($user)->withHeaders(tenantHeaders($tenant));
}

function actingAsUser()
{
    new \Database\Seeders\RolesAndPermissionsSeeder()->run();

    $user = User::factory()->create();
    $user->assignRole('super admin');

    return test()->actingAs($user);
}
