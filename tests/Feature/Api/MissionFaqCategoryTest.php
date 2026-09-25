<?php

use App\Models\MissionFaqCategory;

it('returns a list of mission-faq-categories', function () {
    MissionFaqCategory::factory()->count(3)->create();

    $response = actingAsTenantUser()->getJson(route('api.mission-faq-categories.index'));

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

it('creates a mission-faq-category', function () {
    $response = actingAsTenantUser()->postJson(route('api.mission-faq-categories.store'), [
        'name' => 'Test MissionFaqCategory',
    ]);

    $response->assertSuccessful()->assertJsonPath('data.name', 'Test MissionFaqCategory');

    $this->assertDatabaseHas('mission_faq_categories', [
        'name' => 'Test MissionFaqCategory',
    ]);
});

it('shows a mission-faq-category', function () {
    $item = MissionFaqCategory::factory()->create();

    $response = actingAsTenantUser()->getJson(route('api.mission-faq-categories.show', $item->ulid));

    $response->assertSuccessful()->assertJsonPath('data.ulid', $item->ulid)->assertJsonPath('data.name', $item->name);
});

it('updates a mission-faq-category', function () {
    $item = MissionFaqCategory::factory()->create();

    $response = actingAsTenantUser()->putJson(route('api.mission-faq-categories.update', $item->ulid), [
        'name' => 'Updated Name',
    ]);

    $response->assertSuccessful()->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('mission_faq_categories', [
        'ulid' => $item->ulid,
        'name' => 'Updated Name',
    ]);
});

it('deletes a mission-faq-category', function () {
    $item = MissionFaqCategory::factory()->create();

    $response = actingAsTenantUser()->deleteJson(route('api.mission-faq-categories.destroy', $item->ulid));

    $response->assertStatus(204);

    $this->assertSoftDeleted('mission_faq_categories', [
        'ulid' => $item->ulid,
    ]);
});

it('validates required fields when creating a mission-faq-category', function () {
    $response = actingAsTenantUser()->postJson(route('api.mission-faq-categories.store'), []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['name']);
});
