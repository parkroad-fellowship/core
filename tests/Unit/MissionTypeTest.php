<?php

use App\Models\MissionType;

it('should return a list of mission-types', function () {
    MissionType::factory()->count(3)->create();

    $response = actingAsUser()->getJson(route('api.mission-types.index'));

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

it('should create a mission-type', function () {
    $response = actingAsUser()->postJson(route('api.mission-types.store'), [
        'name' => 'Test MissionType',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Test MissionType');

    $this->assertDatabaseHas('mission_types', [
        'name' => 'Test MissionType',
    ]);
});

it('should show a mission-type', function () {
    $item = MissionType::factory()->create();

    $response = actingAsUser()->getJson(route('api.mission-types.show', $item->ulid));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.ulid', $item->ulid)
        ->assertJsonPath('data.name', $item->name);
});

it('should update a mission-type', function () {
    $item = MissionType::factory()->create();

    $response = actingAsUser()->putJson(route('api.mission-types.update', $item->ulid), [
        'name' => 'Updated Name',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('mission_types', [
        'ulid' => $item->ulid,
        'name' => 'Updated Name',
    ]);
});

it('should delete a mission-type', function () {
    $item = MissionType::factory()->create();

    $response = actingAsUser()->deleteJson(route('api.mission-types.destroy', $item->ulid));

    $response->assertStatus(204);

    $this->assertSoftDeleted('mission_types', [
        'ulid' => $item->ulid,
    ]);
});

it('should validate required fields when creating a mission-type', function () {
    $response = actingAsUser()->postJson(route('api.mission-types.store'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});
