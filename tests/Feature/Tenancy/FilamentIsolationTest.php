<?php

beforeEach(function () {
    tenancy()->end();
});

it('prevents cross-tenant panel access', function () {
    $tenantA = createTenant();
    $tenantB = createTenant();
    $userA = tenantUser($tenantA);

    initTenancy($tenantB);

    $this->actingAs($userA)->get('/admin')->assertStatus(403);
})->skip('Requires domain tenant identification setup in test environment');
