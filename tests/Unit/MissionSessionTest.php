<?php

use App\Enums\PRFMissionStatus;
use App\Models\ClassGroup;
use App\Models\Member;
use App\Models\Mission;
use Database\Factories\MissionSessionFactory;
use Illuminate\Support\Facades\Artisan;

it('should return a list of mission sessions', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route('api.mission-sessions.index', [
        'include' => 'facilitator,speaker,classGroup',
    ]));

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'entity',
                    'ulid',
                    'starts_at',
                    'ends_at',
                    'notes',
                    'facilitator' => [
                        'entity',
                        'ulid',
                        'first_name',
                        'last_name',
                    ],
                    'speaker' => [
                        'entity',
                        'ulid',
                        'first_name',
                        'last_name',
                    ],
                    'class_group' => [
                        'entity',
                        'ulid',
                        'name',
                    ],
                ],
            ],
        ]);
});

it('should allow for a member to add a new session', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $mission = Mission::factory()->create([
        'status' => PRFMissionStatus::APPROVED,
    ]);

    $data = (new MissionSessionFactory)->raw();

    // Act
    $response = actingAsUser()->post(route('api.mission-sessions.store', [
        'include' => 'facilitator,speaker,classGroup',
    ]), [
        'mission_ulid' => $mission->ulid,
        'facilitator_ulid' => Member::query()->where('id', $data['facilitator_id'])->first()->ulid,
        'speaker_ulid' => Member::query()->where('id', $data['speaker_id'])->first()?->ulid,
        'class_group_ulid' => ClassGroup::query()->where('id', $data['class_group_id'])->first()?->ulid,
        'starts_at' => now()->addDays(2)->toDateTimeString(),
        'ends_at' => now()->addDays(2)->addHours(2)->toDateTimeString(),
        'notes' => $data['notes'],
    ]);

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'starts_at',
                'ends_at',
                'notes',
                'facilitator' => [
                    'entity',
                    'ulid',
                    'first_name',
                    'last_name',
                ],
                'speaker' => [
                    'entity',
                    'ulid',
                    'first_name',
                    'last_name',
                ],
                'class_group' => [
                    'entity',
                    'ulid',
                    'name',
                ],
            ],
        ]);
});

it('should allow a member to update a mission session', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $mission = Mission::factory()->create([
        'status' => PRFMissionStatus::APPROVED,
    ]);

    $missionSession = Mission::factory()->create([
        'mission_id' => $mission->id,
    ]);

    $data = (new MissionSessionFactory)->raw();

    // Act
    $response = actingAsUser()->put(route('api.mission-sessions.update', [
        'mission_session' => $missionSession->ulid,
        'include' => 'facilitator,speaker,classGroup',
    ]), [
        'mission_ulid' => $mission->ulid,
        'facilitator_ulid' => Member::query()->where('id', $data['facilitator_id'])->first()->ulid,
        'speaker_ulid' => Member::query()->where('id', $data['speaker_id'])->first()?->ulid,
        'class_group_ulid' => ClassGroup::query()->where('id', $data['class_group_id'])->first()?->ulid,
        'starts_at' => now()->addDays(2)->toDateTimeString(),
        'ends_at' => now()->addDays(2)->addHours(2)->toDateTimeString(),
        'notes' => $data['notes'],
    ]);

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'starts_at',
                'ends_at',
                'notes',
                'facilitator' => [
                    'entity',
                    'ulid',
                    'first_name',
                    'last_name',
                ],
                'speaker' => [
                    'entity',
                    'ulid',
                    'first_name',
                    'last_name',
                ],
                'class_group' => [
                    'entity',
                    'ulid',
                    'name',
                ],
            ],
        ]);
});
