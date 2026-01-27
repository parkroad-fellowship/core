<?php

use App\Models\Church;

it('should return a list of churches', function () {
    Church::factory()->count(3)->create();

    $response = actingAsUser()->getJson(route('api.churches.index'));

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

it('should create a church', function () {
    $response = actingAsUser()->postJson(route('api.churches.store'), [
        'name' => 'Test Church',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Test Church');

    $this->assertDatabaseHas('churches', [
        'name' => 'Test Church',
    ]);
});

it('should show a church', function () {
    $item = Church::factory()->create();

    $response = actingAsUser()->getJson(route('api.churches.show', $item->ulid));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.ulid', $item->ulid)
        ->assertJsonPath('data.name', $item->name);
});

it('should update a church', function () {
    $item = Church::factory()->create();

    $response = actingAsUser()->putJson(route('api.churches.update', $item->ulid), [
        'name' => 'Updated Name',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('churches', [
        'ulid' => $item->ulid,
        'name' => 'Updated Name',
    ]);
});

it('should delete a church', function () {
    $item = Church::factory()->create();

    $response = actingAsUser()->deleteJson(route('api.churches.destroy', $item->ulid));

    $response->assertStatus(204);

    $this->assertSoftDeleted('churches', [
        'ulid' => $item->ulid,
    ]);
});

it('should validate required fields when creating a church', function () {
    $response = actingAsUser()->postJson(route('api.churches.store'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});
