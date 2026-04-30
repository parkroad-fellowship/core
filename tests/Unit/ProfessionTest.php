<?php

use App\Models\Profession;

it('should return a list of professions', function () {
    Profession::factory()->count(3)->create();

    $response = actingAsUser()->getJson(route('api.professions.index'));

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

it('should create a profession', function () {
    $response = actingAsUser()->postJson(route('api.professions.store'), [
        'name' => 'Test Profession',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Test Profession');

    $this->assertDatabaseHas('professions', [
        'name' => 'Test Profession',
    ]);
});

it('should show a profession', function () {
    $item = Profession::factory()->create();

    $response = actingAsUser()->getJson(route('api.professions.show', $item->ulid));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.ulid', $item->ulid)
        ->assertJsonPath('data.name', $item->name);
});

it('should update a profession', function () {
    $item = Profession::factory()->create();

    $response = actingAsUser()->putJson(route('api.professions.update', $item->ulid), [
        'name' => 'Updated Name',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('professions', [
        'ulid' => $item->ulid,
        'name' => 'Updated Name',
    ]);
});

it('should delete a profession', function () {
    $item = Profession::factory()->create();

    $response = actingAsUser()->deleteJson(route('api.professions.destroy', $item->ulid));

    $response->assertStatus(204);

    $this->assertSoftDeleted('professions', [
        'ulid' => $item->ulid,
    ]);
});

it('should validate required fields when creating a profession', function () {
    $response = actingAsUser()->postJson(route('api.professions.store'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});
