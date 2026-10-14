<?php

use App\Models\Church;

it('returns a list of churches', function () {
    Church::factory()->count(3)->create();

    $response = actingAsTenantUser()->getJson(route('api.churches.index'));

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

it('creates a church', function () {
    $response = actingAsTenantUser()->postJson(route('api.churches.store'), [
        'name' => 'Test Church',
    ]);

    $response->assertSuccessful()->assertJsonPath('data.name', 'Test Church');

    $this->assertDatabaseHas('churches', [
        'name' => 'Test Church',
    ]);
});

it('shows a church', function () {
    $item = Church::factory()->create();

    $response = actingAsTenantUser()->getJson(route('api.churches.show', $item->ulid));

    $response->assertSuccessful()->assertJsonPath('data.ulid', $item->ulid)->assertJsonPath('data.name', $item->name);
});

it('updates a church', function () {
    $item = Church::factory()->create();

    $response = actingAsTenantUser()->putJson(route('api.churches.update', $item->ulid), [
        'name' => 'Updated Name',
    ]);

    $response->assertSuccessful()->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('churches', [
        'ulid' => $item->ulid,
        'name' => 'Updated Name',
    ]);
});

it('deletes a church', function () {
    $item = Church::factory()->create();

    $response = actingAsTenantUser()->deleteJson(route('api.churches.destroy', $item->ulid));

    $response->assertStatus(204);

    $this->assertSoftDeleted('churches', [
        'ulid' => $item->ulid,
    ]);
});

it('validates required fields when creating a church', function () {
    $response = actingAsTenantUser()->postJson(route('api.churches.store'), []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
});
