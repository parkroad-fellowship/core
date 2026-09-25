<?php

use App\Models\Mission;
use App\Models\MissionType;
use App\Models\School;
use App\Models\SchoolTerm;

beforeEach(function () {
    tenancy()->end();
    new \Database\Seeders\RolesAndPermissionsSeeder()->run();
});

it('hides another tenant\'s records', function () {
    $tenantA = createTenant();
    $userA = tenantUser($tenantA);
    School::factory()->create();
    MissionType::factory()->create();
    SchoolTerm::factory()->create();
    $mission = Mission::factory()->create();

    $tenantB = createTenant();
    $userB = tenantUser($tenantB);

    $this
        ->actingAs($userB)
        ->withHeaders(tenantHeaders($tenantB))
        ->getJson(route('api.missions.show', $mission->ulid))
        ->assertNotFound();
});

it('lists only own tenant records', function () {
    $tenantA = createTenant();
    $userA = tenantUser($tenantA);
    School::factory()->create();
    MissionType::factory()->create();
    SchoolTerm::factory()->create();
    Mission::factory()->count(3)->create();

    $tenantB = createTenant();
    $userB = tenantUser($tenantB);
    School::factory()->create();
    MissionType::factory()->create();
    SchoolTerm::factory()->create();
    Mission::factory()->count(5)->create();

    $response = $this->actingAs($userB)->withHeaders(tenantHeaders($tenantB))->getJson(route('api.missions.index'));
    $response->assertJsonCount(5, 'data');
});
