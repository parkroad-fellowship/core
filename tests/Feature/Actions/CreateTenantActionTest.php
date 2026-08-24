<?php

use App\Actions\Tenant\CreateTenantAction;
use App\Models\Tenant;

it('creates a tenant with name and slug', function () {
    $tenant = app(CreateTenantAction::class)->handle(name: 'Test Fellowship', slug: 'test-fellowship');

    expect($tenant)->toBeInstanceOf(Tenant::class);
    expect($tenant->name)->toBe('Test Fellowship');
    expect($tenant->slug)->toBe('test-fellowship');
    expect($tenant->is_active)->toBeTrue();
});

it('auto-generates slug when omitted', function () {
    $tenant = app(CreateTenantAction::class)->handle(name: 'Auto Slug Fellowship');

    expect($tenant->slug)->not->toBeEmpty();
    expect($tenant->slug)->toBe(Str::slug('Auto Slug Fellowship'));
});

it('adds custom domain when provided', function () {
    $tenant = app(CreateTenantAction::class)->handle(
        name: 'Domain Test',
        slug: 'domain-test',
        customDomain: 'custom.example.com',
    );

    expect($tenant->domains->pluck('domain'))->toContain('custom.example.com');
});

it('creates default domain from slug via observer', function () {
    $tenant = app(CreateTenantAction::class)->handle(name: 'Observer Domain Test', slug: 'observer-domain-test');

    expect($tenant->domains->pluck('domain'))->toContain('observer-domain-test.prf.test');
});

it('creates tenants with unique slugs for same name', function () {
    $first = app(CreateTenantAction::class)->handle(name: 'Parkroad Fellowship');

    $second = app(CreateTenantAction::class)->handle(name: 'Parkroad Fellowship');

    expect($first->slug)->toBe('parkroad-fellowship');
    expect($second->slug)->toBe('parkroad-fellowship-1');
    expect($first->id)->not->toBe($second->id);
});
