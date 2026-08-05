<?php

use App\Services\Firebase\TenantFirebaseFactory;

it('creates a factory instance when tenancy is not initialized', function () {
    $factory = new TenantFirebaseFactory;
    expect($factory->getFactory())->not->toBeNull();
});

it('resets factory instance', function () {
    $factory = new TenantFirebaseFactory;
    $factory->getFactory();
    $factory->reset();

    $reflection = new ReflectionClass($factory);
    $property = $reflection->getProperty('factory');
    $property->setAccessible(true);

    expect($property->getValue($factory))->toBeNull();
});
