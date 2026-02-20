<?php

use App\Enums\PRFMissionStatus;
use App\Models\Mission;
use Database\Factories\MissionQuestionFactory;
use Illuminate\Support\Facades\Artisan;

it('should return a list of questions curated for students', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route(
        'api.mission-questions.index',
        [
            'include' => 'mission',
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
                    'question',
                    'mission',
                ],
            ],
        ]);
});

it('should allow a user to record a question asked by a student', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $mission = Mission::factory()->create([
        'status' => PRFMissionStatus::APPROVED,
    ]);

    $data = (new MissionQuestionFactory)->raw();

    // Act
    $response = actingAsUser()->post(
        route('api.mission-questions.store', [
            'include' => 'mission',
        ]),
        [
            'question' => $data['question'],
            'mission_ulid' => $mission->ulid,
        ],
    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'question',
                'mission',
            ],
        ]);
});

it('should allow a user to update a mission question', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $mission = Mission::factory()->create([
        'status' => PRFMissionStatus::APPROVED,
    ]);

    $data = (new MissionQuestionFactory)->raw();

    $result = actingAsUser()->post(
        route('api.mission-questions.store'),
        [
            'question' => $data['question'],
            'mission_ulid' => $mission->ulid,
        ],
    );

    // Act
    $response = actingAsUser()->put(
        route(
            'api.mission-questions.update',

            [
                'ulid' => $result->json('data.ulid'),
                'include' => 'mission',
            ],
        ),
        [
            'mission_ulid' => $mission->ulid,
            'question' => 'Cool Beans',
        ],
    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'question',
                'mission',
            ],
        ]);

    expect($response->json('data.question'))->toBe('Cool Beans');
    expect($response->json('data.question'))->not->toBe($data['question']);
});
