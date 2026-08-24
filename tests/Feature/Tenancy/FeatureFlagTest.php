<?php

use App\Models\AppSetting;

beforeEach(function () {
    tenancy()->end();
    new \Database\Seeders\RolesAndPermissionsSeeder()->run();
});

it('blocks disabled features via API', function () {
    $tenant = createTenant();
    initTenancy($tenant);
    $user = actingAsTenantUser($tenant);
    AppSetting::set('feature.missions', '0', 'features', 'boolean');

    $this->actingAs($user)->withHeaders(tenantHeaders($tenant))->getJson('/api/v1/missions')->assertStatus(403);
});
