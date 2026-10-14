<?php

use App\Models\MissionType;

it('returns a list of mission-types', function () {
    MissionType::factory()->count(3)->create();

    $response = actingAsTenantUser()->getJson(route('api.mission-types.index'));

    $response
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'entity',
                    'ulid',
                    'name',
                    'is_active',
                ],
            ],
        ]);
});

it('creates a mission-type', function () {
    $response = actingAsTenantUser()->postJson(route('api.mission-types.store'), [
        'name' => 'Test MissionType',
    ]);

    $response->assertSuccessful()->assertJsonPath('data.name', 'Test MissionType');

    $this->assertDatabaseHas('mission_types', [
        'name' => 'Test MissionType',
    ]);
});

it('shows a mission-type', function () {
    $item = MissionType::factory()->create();

    $response = actingAsTenantUser()->getJson(route('api.mission-types.show', $item->ulid));

    $response->assertSuccessful()->assertJsonPath('data.ulid', $item->ulid)->assertJsonPath('data.name', $item->name);
});

it('updates a mission-type', function () {
    $item = MissionType::factory()->create();

    $response = actingAsTenantUser()->putJson(route('api.mission-types.update', $item->ulid), [
        'name' => 'Updated Name',
    ]);

    $response->assertSuccessful()->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('mission_types', [
        'ulid' => $item->ulid,
        'name' => 'Updated Name',
    ]);
});

it('deletes a mission-type', function () {
    $item = MissionType::factory()->create();

    $response = actingAsTenantUser()->deleteJson(route('api.mission-types.destroy', $item->ulid));

    $response->assertStatus(204);

    $this->assertSoftDeleted('mission_types', [
        'ulid' => $item->ulid,
    ]);
});

it('validates required fields when creating a mission-type', function () {
    $response = actingAsTenantUser()->postJson(route('api.mission-types.store'), []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
});
