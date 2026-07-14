<?php

use App\Models\Mission;
use App\Models\MissionType;
use App\Models\School;
use App\Models\SchoolTerm;

beforeEach(function () {
    tenancy()->end();
    (new \Database\Seeders\RolesAndPermissionsSeeder)->run();
});

it('prevents cross-tenant read', function () {
    $tenantA = createTenant();
    initTenancy($tenantA);
    $userA = actingAsTenantUser($tenantA);
    School::factory()->create();
    MissionType::factory()->create();
    SchoolTerm::factory()->create();
    $mission = Mission::factory()->create();

    $tenantB = createTenant();
    initTenancy($tenantB);
    $userB = actingAsTenantUser($tenantB);

    $this->actingAs($userB)
        ->withHeaders(tenantHeaders($tenantB))
        ->getJson("/api/v1/missions/{$mission->ulid}")
        ->assertStatus(403);
});

it('lists only own tenant records', function () {
    $tenantA = createTenant();
    initTenancy($tenantA);
    $userA = actingAsTenantUser($tenantA);
    School::factory()->create();
    MissionType::factory()->create();
    SchoolTerm::factory()->create();
    Mission::factory()->count(3)->create();

    $tenantB = createTenant();
    initTenancy($tenantB);
    $userB = actingAsTenantUser($tenantB);
    School::factory()->create();
    MissionType::factory()->create();
    SchoolTerm::factory()->create();
    Mission::factory()->count(5)->create();

    $response = $this->actingAs($userB)
        ->withHeaders(tenantHeaders($tenantB))
        ->getJson('/api/v1/missions');
    $response->assertJsonCount(5, 'data');
});
