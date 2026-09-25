<?php

use App\Actions\Tenant\CreateTenantAction;
use App\Enums\PRFMemberEmailMode;
use App\Helpers\Utils;
use App\Notifications\Tenant\TenantProvisionedNotification;
use Illuminate\Support\Facades\Notification;

it('stores how members sign in and the Workspace domain chosen at creation', function () {
    Notification::fake();

    $tenant = app(CreateTenantAction::class)->handle(
        name: 'Hope Fellowship',
        shouldProvision: true,
        adminEmail: 'admin@hope.test',
        memberEmailMode: PRFMemberEmailMode::ORGANISATION_DOMAIN,
        orgEmailDomain: 'HopeFellowship.org',
    );

    initTenancy($tenant);

    expect(Utils::memberEmailMode())
        ->toBe(PRFMemberEmailMode::ORGANISATION_DOMAIN)
        ->and(Utils::getOrgEmailDomain())
        ->toBe('hopefellowship.org');

    Notification::assertSentTo(
        \App\Models\User::query()->where('email', 'admin@hope.test')->sole(),
        TenantProvisionedNotification::class,
        fn(TenantProvisionedNotification $notification) => (
            $notification->memberEmailMode === PRFMemberEmailMode::ORGANISATION_DOMAIN
        ),
    );
});

it('refuses a public webmail domain for organisation-domain tenants', function () {
    app(CreateTenantAction::class)->handle(
        name: 'Webmail Fellowship',
        shouldProvision: true,
        memberEmailMode: PRFMemberEmailMode::ORGANISATION_DOMAIN,
        orgEmailDomain: 'gmail.com',
    );
})->throws(RuntimeException::class, 'Organisation-domain tenants need their own (non-webmail) email domain.');

it('defaults new tenants to personal email sign in', function () {
    $tenant = app(CreateTenantAction::class)->handle(name: 'Grace Fellowship', shouldProvision: true);

    initTenancy($tenant);

    expect(Utils::memberEmailMode())->toBe(PRFMemberEmailMode::PERSONAL)->and(Utils::getOrgEmailDomain())->toBeNull();
});
