<?php

use App\Models\MaritalStatus;

it('should return a list of marital-statuses', function () {
    MaritalStatus::factory()->count(3)->create();

    $response = actingAsTenantUser->getJson(route('api.marital-statuses.index'));

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

it('should create a marital-status', function () {
    $response = actingAsTenantUser->postJson(route('api.marital-statuses.store'), [
        'name' => 'Test MaritalStatus',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Test MaritalStatus');

    $this->assertDatabaseHas('marital_statuses', [
        'name' => 'Test MaritalStatus',
    ]);
});

it('should show a marital-status', function () {
    $item = MaritalStatus::factory()->create();

    $response = actingAsTenantUser->getJson(route('api.marital-statuses.show', $item->ulid));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.ulid', $item->ulid)
        ->assertJsonPath('data.name', $item->name);
});

it('should update a marital-status', function () {
    $item = MaritalStatus::factory()->create();

    $response = actingAsTenantUser->putJson(route('api.marital-statuses.update', $item->ulid), [
        'name' => 'Updated Name',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('marital_statuses', [
        'ulid' => $item->ulid,
        'name' => 'Updated Name',
    ]);
});

it('should delete a marital-status', function () {
    $item = MaritalStatus::factory()->create();

    $response = actingAsTenantUser->deleteJson(route('api.marital-statuses.destroy', $item->ulid));

    $response->assertStatus(204);

    $this->assertSoftDeleted('marital_statuses', [
        'ulid' => $item->ulid,
    ]);
});

it('should validate required fields when creating a marital-status', function () {
    $response = actingAsTenantUser->postJson(route('api.marital-statuses.store'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});
