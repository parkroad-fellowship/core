<?php

use App\Enums\PRFIntegration;
use App\Exceptions\IntegrationNotConfiguredException;
use App\Models\AppSetting;
use App\Models\Tenant;
use App\Services\Tenancy\TenantIntegrations;
use Illuminate\Support\Facades\DB;

$paystack = [
    'payments.paystack_secret_key' => 'sk_test_tenant_a',
    'payments.paystack_public_key' => 'pk_test_tenant_a',
    'payments.paystack_callback_url' => 'https://tenant-a.test/payments/done',
];

it('loads the tenant\'s own credentials into config', function () use ($paystack) {
    configureIntegration($paystack);

    expect(config('prf.payments.paystack.secret_key'))
        ->toBe('sk_test_tenant_a')
        ->and(config('prf.payments.paystack.currency'))
        ->toBe('KES')
        ->and(app(TenantIntegrations::class)->isConfigured(PRFIntegration::PAYSTACK))
        ->toBeTrue();
});

it('never falls back to platform credentials', function () {
    config(['prf.payments.paystack.secret_key' => 'sk_platform_should_not_be_used']);

    app(TenantIntegrations::class)->load();

    expect(config('prf.payments.paystack.secret_key'))->toBeNull();

    app(TenantIntegrations::class)->require(PRFIntegration::PAYSTACK);
})->throws(IntegrationNotConfiguredException::class, 'Paystack payments is not configured for this organisation.');

it('clears tenant credentials when tenancy ends', function () use ($paystack) {
    configureIntegration($paystack);

    tenancy()->end();

    expect(config('prf.payments.paystack.secret_key'))->toBeNull();
});

it('keeps each tenant\'s credentials separate', function () use ($paystack) {
    $tenantA = tenant();
    configureIntegration($paystack);

    $tenantB = Tenant::factory()->create();
    initTenancy($tenantB);
    configureIntegration([...$paystack, 'payments.paystack_secret_key' => 'sk_test_tenant_b']);

    expect(config('prf.payments.paystack.secret_key'))->toBe('sk_test_tenant_b');

    initTenancy($tenantA);

    expect(config('prf.payments.paystack.secret_key'))->toBe('sk_test_tenant_a');
});

it('stores secret settings encrypted at rest', function () use ($paystack) {
    configureIntegration($paystack);

    $stored = DB::table('app_settings')->where('key', 'payments.paystack_secret_key')->value('value');

    expect($stored)
        ->toStartWith(AppSetting::ENCRYPTED_PREFIX)
        ->not
        ->toContain('sk_test_tenant_a')
        ->and(AppSetting::get('payments.paystack_secret_key'))
        ->toBe('sk_test_tenant_a');
});

it('reports which integrations are not configured yet', function () use ($paystack) {
    configureIntegration($paystack);

    expect(app(TenantIntegrations::class)->unconfigured())
        ->not
        ->toContain(PRFIntegration::PAYSTACK)
        ->toContain(PRFIntegration::SMS, PRFIntegration::AI, PRFIntegration::FCM);
});
