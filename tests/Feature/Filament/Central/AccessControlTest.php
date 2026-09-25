<?php

use App\Filament\Central\Resources\TenantResource;
use App\Models\CentralSetting;
use App\Models\User;
use Filament\Facades\Filament;

it('allows any user when no admin emails are configured (bootstrap)', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(TenantResource::getUrl('index'))->assertSuccessful();
});

it('allows listed email to access central panel', function () {
    $user = User::factory()->create(['email' => 'admin@example.com']);

    CentralSetting::set('admin_emails', ['admin@example.com'], 'admin', 'array');

    $this->actingAs($user);

    $this->get(TenantResource::getUrl('index'))->assertSuccessful();
});

it('denies unlisted email when admin emails are configured', function () {
    $user = User::factory()->create(['email' => 'other@example.com']);

    CentralSetting::set('admin_emails', ['admin@example.com'], 'admin', 'array');

    $this->actingAs($user);

    $this->get(TenantResource::getUrl('index'))->assertForbidden();
});

it('central panel uses correct panel id', function () {
    $panel = Filament::getPanel('central');

    expect($panel->getId())->toBe('central');
});

it('central panel is configured with correct domain', function () {
    $panel = Filament::getPanel('central');

    expect($panel->getDomains())->toBe([config('tenancy.identification.central_domains', ['prf.test'])[0]]);
});
