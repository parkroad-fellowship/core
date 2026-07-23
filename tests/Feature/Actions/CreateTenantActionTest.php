<?php

use App\Actions\Tenant\CreateTenantAction;
use App\Jobs\Tenant\ProvisionTenantJob;
use App\Models\Tenant;

it('creates a tenant with name and slug', function () {
    $tenant = app(CreateTenantAction::class)->handle(
        name: 'Test Fellowship',
        slug: 'test-fellowship',
        shouldSeedDomains: false,
    );

    expect($tenant)->toBeInstanceOf(Tenant::class);
    expect($tenant->name)->toBe('Test Fellowship');
    expect($tenant->slug)->toBe('test-fellowship');
    expect($tenant->is_active)->toBeTrue();
});

it('auto-generates slug when omitted', function () {
    $tenant = app(CreateTenantAction::class)->handle(
        name: 'Auto Slug Fellowship',
        shouldSeedDomains: false,
    );

    expect($tenant->slug)->not->toBeEmpty();
    expect($tenant->slug)->toBe(Str::slug('Auto Slug Fellowship'));
});

it('adds custom domain when provided', function () {
    $tenant = app(CreateTenantAction::class)->handle(
        name: 'Domain Test',
        slug: 'domain-test',
        customDomain: 'custom.example.com',
        shouldSeedDomains: false,
    );

    expect($tenant->domains->pluck('domain'))->toContain('custom.example.com');
});

it('seeds environment domains by default', function () {
    $tenant = app(CreateTenantAction::class)->handle(
        name: 'Domain Seed Test',
        slug: 'domain-seed-test',
    );

    expect($tenant->domains->count())->toBeGreaterThan(0);
});

it('skips domain seeding when disabled', function () {
    $tenant = app(CreateTenantAction::class)->handle(
        name: 'No Seed Test',
        slug: 'no-seed-test',
        shouldSeedDomains: false,
    );

    expect($tenant->domains->count())->toBe(0);
});

it('dispatches provision job when requested', function () {
    ProvisionTenantJob::fake();

    app(CreateTenantAction::class)->handle(
        name: 'Provision Test',
        slug: 'provision-test',
        shouldSeedDomains: false,
        shouldProvision: true,
        adminEmail: 'admin@example.com',
    );

    ProvisionTenantJob::assertDispatched();
});

it('does not dispatch provision job by default', function () {
    ProvisionTenantJob::fake();

    app(CreateTenantAction::class)->handle(
        name: 'No Provision Test',
        slug: 'no-provision-test',
        shouldSeedDomains: false,
    );

    ProvisionTenantJob::assertNotDispatched();
});

it('is idempotent for default tenant creation', function () {
    $first = app(CreateTenantAction::class)->handle(
        name: 'Parkroad Fellowship',
        shouldSeedDomains: false,
    );

    $second = app(CreateTenantAction::class)->handle(
        name: 'Parkroad Fellowship',
        shouldSeedDomains: false,
    );

    expect($first->id)->not->toBe($second->id);
});
