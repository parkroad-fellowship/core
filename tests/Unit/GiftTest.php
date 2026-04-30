<?php

use App\Models\Gift;

it('should return a list of gifts', function () {
    Gift::factory()->count(3)->create();

    $response = actingAsUser()->getJson(route('api.gifts.index'));

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

it('should create a gift', function () {
    $response = actingAsUser()->postJson(route('api.gifts.store'), [
        'name' => 'Test Gift',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Test Gift');

    $this->assertDatabaseHas('gifts', [
        'name' => 'Test Gift',
    ]);
});

it('should show a gift', function () {
    $item = Gift::factory()->create();

    $response = actingAsUser()->getJson(route('api.gifts.show', $item->ulid));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.ulid', $item->ulid)
        ->assertJsonPath('data.name', $item->name);
});

it('should update a gift', function () {
    $item = Gift::factory()->create();

    $response = actingAsUser()->putJson(route('api.gifts.update', $item->ulid), [
        'name' => 'Updated Name',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('gifts', [
        'ulid' => $item->ulid,
        'name' => 'Updated Name',
    ]);
});

it('should delete a gift', function () {
    $item = Gift::factory()->create();

    $response = actingAsUser()->deleteJson(route('api.gifts.destroy', $item->ulid));

    $response->assertStatus(204);

    $this->assertSoftDeleted('gifts', [
        'ulid' => $item->ulid,
    ]);
});

it('should validate required fields when creating a gift', function () {
    $response = actingAsUser()->postJson(route('api.gifts.store'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});
