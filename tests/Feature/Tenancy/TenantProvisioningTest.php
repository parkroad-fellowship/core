<?php

use App\Actions\Tenant\CreateTenantAction;
use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFMemberEmailMode;
use App\Helpers\Utils;
use App\Models\ExpenseCategory;
use App\Models\FinancialAccount;
use App\Models\LedgerCategory;
use App\Models\PaymentType;
use App\Models\TransferRate;
use App\Notifications\Tenant\TenantProvisionedNotification;
use App\Services\Finance\ChartOfAccounts;
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

it('gives new tenants their chart of accounts and finance reference data', function () {
    $tenant = app(CreateTenantAction::class)->handle(name: 'Faith Fellowship', shouldProvision: true);

    initTenancy($tenant);

    expect(FinancialAccount::query()->pluck('name')->all())
        ->toEqualCanonicalizing(['Paybill', 'M-Pesa', 'Bank', 'Cash', 'M-Shwari', 'Paystack'])
        ->and(app(ChartOfAccounts::class)->transactionCosts()->kind)
        ->toBe(PRFLedgerCategoryKind::CHARGE)
        ->and(PaymentType::query()->where('name', 'Missions')->sole()->ledgerCategory?->code)
        ->toBe('income.mission_contribution')
        ->and(ExpenseCategory::query()->count())
        ->toBeGreaterThan(0)
        ->and(TransferRate::query()->count())
        ->toBeGreaterThan(0);
});

it('seeds reference data idempotently for existing tenants', function () {
    $tenant = app(CreateTenantAction::class)->handle(name: 'Joy Fellowship', shouldProvision: true);

    $this->artisan('prf:tenants:seed-reference-data', ['--tenant' => $tenant->id])->assertSuccessful();

    initTenancy($tenant);

    expect(FinancialAccount::query()->count())
        ->toBe(6)
        ->and(LedgerCategory::query()->count())
        ->toBe(count(config('prf.finance.categories')));
});
