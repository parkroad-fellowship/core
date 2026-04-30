<?php

use App\Models\MissionFaqCategory;

it('should return a list of mission-faq-categories', function () {
    MissionFaqCategory::factory()->count(3)->create();

    $response = actingAsUser()->getJson(route('api.mission-faq-categories.index'));

    $response
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'entity',
                    'ulid',
                    'name',
                ],
            ],
        ]);
});

it('should create a mission-faq-category', function () {
    $response = actingAsUser()->postJson(route('api.mission-faq-categories.store'), [
        'name' => 'Test MissionFaqCategory',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Test MissionFaqCategory');

    $this->assertDatabaseHas('mission_faq_categories', [
        'name' => 'Test MissionFaqCategory',
    ]);
});

it('should show a mission-faq-category', function () {
    $item = MissionFaqCategory::factory()->create();

    $response = actingAsUser()->getJson(route('api.mission-faq-categories.show', $item->ulid));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.ulid', $item->ulid)
        ->assertJsonPath('data.name', $item->name);
});

it('should update a mission-faq-category', function () {
    $item = MissionFaqCategory::factory()->create();

    $response = actingAsUser()->putJson(route('api.mission-faq-categories.update', $item->ulid), [
        'name' => 'Updated Name',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('mission_faq_categories', [
        'ulid' => $item->ulid,
        'name' => 'Updated Name',
    ]);
});

it('should delete a mission-faq-category', function () {
    $item = MissionFaqCategory::factory()->create();

    $response = actingAsUser()->deleteJson(route('api.mission-faq-categories.destroy', $item->ulid));

    $response->assertStatus(204);

    $this->assertSoftDeleted('mission_faq_categories', [
        'ulid' => $item->ulid,
    ]);
});

it('should validate required fields when creating a mission-faq-category', function () {
    $response = actingAsUser()->postJson(route('api.mission-faq-categories.store'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});
