<?php

use App\Enums\PRFMissionGroundSuggestionStatus;
use App\Models\Member;
use Database\Factories\MissionGroundSuggestionFactory;
use Illuminate\Support\Facades\Artisan;

it('should return a list of mission ground suggestions', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route(
        'api.mission-ground-suggestions.index',
        [
            'include' => 'suggestor',
        ]
    ));

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'entity',
                    'ulid',
                    'name',
                    'contact_person',
                    'contact_number',
                    'status',
                    'notes',
                    'suggestor',
                ],
            ],
        ]);
});

it('should allow a user to record a mission ground suggestion', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $data = (new MissionGroundSuggestionFactory)->raw();

    // Act
    $response = actingAsUser()->post(
        route('api.mission-ground-suggestions.store', [
            'include' => 'suggestor',
        ]),
        [
            'suggestor_ulid' => Member::query()->find($data['suggestor_id'])->ulid,
            ...$data,
        ],
    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'name',
                'contact_person',
                'contact_number',
                'status',
                'notes',
                'suggestor',
            ],
        ]);
});

it('should allow a user to update a mission ground suggestion', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $data = (new MissionGroundSuggestionFactory)->raw();

    $result = actingAsUser()->post(
        route('api.mission-ground-suggestions.store'),
        [
            'suggestor_ulid' => Member::query()->find($data['suggestor_id'])->ulid,
            ...$data,
        ],
    );

    // Act
    $response = actingAsUser()->put(
        route(
            'api.mission-ground-suggestions.update',
            [
                'ulid' => $result->json('data.ulid'),
                'include' => 'suggestor',
            ],
        ),
        [
            'suggestor_ulid' => Member::query()->find($data['suggestor_id'])->ulid,
            ...$data,
            'status' => PRFMissionGroundSuggestionStatus::INITIATED_CONTACT->value,
        ],
    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'name',
                'contact_person',
                'contact_number',
                'status',
                'notes',
                'suggestor',
            ],
        ]);

    expect($response->json('data.status'))->toBe(PRFMissionGroundSuggestionStatus::INITIATED_CONTACT->value);
    expect($response->json('data.status'))->not->toBe($data['status']);
});
