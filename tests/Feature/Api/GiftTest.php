<?php

use App\Models\Gift;

it('returns a list of gifts', function () {
    Gift::factory()->count(3)->create();

    $response = actingAsTenantUser()->getJson(route('api.gifts.index'));

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

it('creates a gift', function () {
    $response = actingAsTenantUser()->postJson(route('api.gifts.store'), [
        'name' => 'Test Gift',
    ]);

    $response->assertSuccessful()->assertJsonPath('data.name', 'Test Gift');

    $this->assertDatabaseHas('gifts', [
        'name' => 'Test Gift',
    ]);
});

it('shows a gift', function () {
    $item = Gift::factory()->create();

    $response = actingAsTenantUser()->getJson(route('api.gifts.show', $item->ulid));

    $response->assertSuccessful()->assertJsonPath('data.ulid', $item->ulid)->assertJsonPath('data.name', $item->name);
});

it('updates a gift', function () {
    $item = Gift::factory()->create();

    $response = actingAsTenantUser()->putJson(route('api.gifts.update', $item->ulid), [
        'name' => 'Updated Name',
    ]);

    $response->assertSuccessful()->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('gifts', [
        'ulid' => $item->ulid,
        'name' => 'Updated Name',
    ]);
});

it('deletes a gift', function () {
    $item = Gift::factory()->create();

    $response = actingAsTenantUser()->deleteJson(route('api.gifts.destroy', $item->ulid));

    $response->assertStatus(204);

    $this->assertSoftDeleted('gifts', [
        'ulid' => $item->ulid,
    ]);
});

it('validates required fields when creating a gift', function () {
    $response = actingAsTenantUser()->postJson(route('api.gifts.store'), []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
});
