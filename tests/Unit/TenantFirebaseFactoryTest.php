<?php

use App\Exceptions\IntegrationNotConfiguredException;
use App\Services\Firebase\TenantFirebaseFactory;

function fakeServiceAccount(): string
{
    return json_encode([
        'type' => 'service_account',
        'project_id' => 'prf-tenant-test',
        'client_email' => 'firebase@prf-tenant-test.iam.gserviceaccount.com',
        'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIB\n-----END PRIVATE KEY-----\n",
    ]);
}

it('refuses to build a Firebase client when the tenant has not configured Firebase', function () {
    new TenantFirebaseFactory()->getFactory();
})->throws(IntegrationNotConfiguredException::class);

it('builds the Firebase client from the tenant\'s own service account', function () {
    configureIntegration(['firebase.service_account_json' => fakeServiceAccount()]);

    expect(new TenantFirebaseFactory()->getFactory())->not->toBeNull();
});

it('forgets the client so the next tenant gets its own', function () {
    configureIntegration(['firebase.service_account_json' => fakeServiceAccount()]);

    $factory = new TenantFirebaseFactory();
    $factory->getFactory();
    $factory->reset();

    $property = new ReflectionProperty($factory, 'factory');

    expect($property->getValue($factory))->toBeNull();
});
