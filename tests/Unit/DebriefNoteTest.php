<?php

use App\Enums\PRFMissionStatus;
use App\Models\Mission;
use Database\Factories\DebriefNoteFactory;
use Illuminate\Support\Facades\Artisan;

it('should return a list of notes made at debrief sessions', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route(
        'api.debrief-notes.index',
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
                    'note',
                    'mission',
                ],
            ],
        ]);
});

it('should allow a user to record a note made at a debrief session', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $mission = Mission::factory()->create([
        'status' => PRFMissionStatus::APPROVED,
    ]);

    $data = (new DebriefNoteFactory)->raw();

    // Act
    $response = actingAsUser()->post(
        route('api.debrief-notes.store', [
            'include' => 'mission',
        ]),
        [
            'note' => $data['note'],
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
                'note',
                'mission',
            ],
        ]);
});

it('should allow a user to update a debrief note', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $mission = Mission::factory()->create([
        'status' => PRFMissionStatus::APPROVED,
    ]);

    $data = (new DebriefNoteFactory)->raw();

    $result = actingAsUser()->post(
        route('api.debrief-notes.store'),
        [
            'note' => $data['note'],
            'mission_ulid' => $mission->ulid,
        ],
    );

    // Act
    $response = actingAsUser()->put(
        route(
            'api.debrief-notes.update',

            [
                'ulid' => $result->json('data.ulid'),
                'include' => 'mission',
            ],
        ),
        [
            'mission_ulid' => $mission->ulid,
            'note' => 'Cool Beans',
        ],
    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'note',
                'mission',
            ],
        ]);

    expect($response->json('data.note'))->toBe('Cool Beans');
    expect($response->json('data.note'))->not->toBe($data['note']);
});
