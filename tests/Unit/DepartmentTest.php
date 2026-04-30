<?php

use App\Models\Department;

it('should return a list of departments', function () {
    Department::factory()->count(3)->create();

    $response = actingAsUser()->getJson(route('api.departments.index'));

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

it('should create a department', function () {
    $response = actingAsUser()->postJson(route('api.departments.store'), [
        'name' => 'Test Department',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Test Department');

    $this->assertDatabaseHas('departments', [
        'name' => 'Test Department',
    ]);
});

it('should show a department', function () {
    $item = Department::factory()->create();

    $response = actingAsUser()->getJson(route('api.departments.show', $item->ulid));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.ulid', $item->ulid)
        ->assertJsonPath('data.name', $item->name);
});

it('should update a department', function () {
    $item = Department::factory()->create();

    $response = actingAsUser()->putJson(route('api.departments.update', $item->ulid), [
        'name' => 'Updated Name',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('departments', [
        'ulid' => $item->ulid,
        'name' => 'Updated Name',
    ]);
});

it('should delete a department', function () {
    $item = Department::factory()->create();

    $response = actingAsUser()->deleteJson(route('api.departments.destroy', $item->ulid));

    $response->assertStatus(204);

    $this->assertSoftDeleted('departments', [
        'ulid' => $item->ulid,
    ]);
});

it('should validate required fields when creating a department', function () {
    $response = actingAsUser()->postJson(route('api.departments.store'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});
