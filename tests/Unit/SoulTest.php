<?php

use App\Enums\PRFActiveStatus;
use App\Enums\PRFMissionStatus;
use App\Models\ClassGroup;
use App\Models\Mission;
use Database\Factories\SoulFactory;
use Illuminate\Support\Facades\Artisan;

it('should return a list of souls', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route(
        'api.souls.index',
        [
            'include' => 'mission,classGroup',
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
                    'full_name',
                    'mission',
                    'class_group',
                ],
            ],
        ]);
});

it('should allow a user to record a soul who made a salvation commitment', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $mission = Mission::factory()->create([
        'status' => PRFMissionStatus::APPROVED,
    ]);

    $classGroup = ClassGroup::factory()->create([
        'is_active' => PRFActiveStatus::ACTIVE,
    ]);

    $data = (new SoulFactory)->raw();

    // Act
    $response = actingAsUser()->post(
        route('api.souls.store', [
            'include' => 'mission,classGroup',
        ]),
        [
            'full_name' => $data['full_name'],
            'mission_ulid' => $mission->ulid,
            'class_group_ulid' => $classGroup->ulid,
        ],
    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'full_name',
                'mission',
                'class_group',
            ],
        ]);
});

it('should allow a user to update a soul who made a salvation commitment', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $mission = Mission::factory()->create([
        'status' => PRFMissionStatus::APPROVED,
    ]);

    $classGroup = ClassGroup::factory()->create([
        'is_active' => PRFActiveStatus::ACTIVE,
    ]);

    $data = (new SoulFactory)->raw();

    $result = actingAsUser()->post(
        route('api.souls.store'),
        [
            'full_name' => $data['full_name'],
            'mission_ulid' => $mission->ulid,
            'class_group_ulid' => $classGroup->ulid,
        ],
    );

    // Act
    $response = actingAsUser()->put(
        route(
            'api.souls.update',

            [
                'ulid' => $result->json('data.ulid'),
                'include' => 'mission,classGroup',
            ],
        ),
        [
            'mission_ulid' => $mission->ulid,
            'class_group_ulid' => $classGroup->ulid,
            'full_name' => 'Cool Beans',
        ],
    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'full_name',
                'mission',
                'class_group',
            ],
        ]);

    expect($response->json('data.full_name'))->toBe('Cool Beans');
    expect($response->json('data.full_name'))->not->toBe($data['full_name']);
});
