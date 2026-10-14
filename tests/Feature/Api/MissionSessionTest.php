<?php

use App\Models\ClassGroup;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionSession;
use App\States\Mission\Approved;
use Database\Factories\MissionSessionFactory;
use Illuminate\Support\Facades\Artisan;

it('returns a list of mission sessions', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsTenantUser()->get(route('api.mission-sessions.index', [
        'include' => 'facilitator,speaker,classGroup,transcripts.media',
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
                    'transcripts' => [
                        '*' => [
                            'entity',
                            'media' => [
                                'entity',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
});

it('returns a single mission session', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
    $missionSession = MissionSession::first();

    // Act
    $response = actingAsTenantUser()->get(route('api.mission-sessions.show', [
        'ulid' => $missionSession->ulid,
        'include' => 'facilitator,speaker,classGroup,transcripts.media',
    ]));

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
                'transcripts' => [
                    '*' => [
                        'entity',
                        'media' => [
                            'entity',
                        ],
                    ],
                ],
            ],
        ]);
});

it('allows for a member to add a new session', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $mission = Mission::factory()->create([
        'status' => Approved::class,
    ]);

    $data = new MissionSessionFactory()->raw();

    // Act
    $response = actingAsTenantUser()->post(
        route('api.mission-sessions.store', [
            'include' => 'facilitator,speaker,classGroup,transcripts.media',
        ]),
        [
            'mission_ulid' => $mission->ulid,
            'facilitator_ulid' => Member::query()->where('id', $data['facilitator_id'])->first()->ulid,
            'speaker_ulid' => Member::query()->where('id', $data['speaker_id'])->first()?->ulid,
            'class_group_ulid' => ClassGroup::query()->where('id', $data['class_group_id'])->first()?->ulid,
            'starts_at' => now()->addDays(2)->toDateTimeString(),
            'ends_at' => now()->addDays(2)->addHours(2)->toDateTimeString(),
            'notes' => $data['notes'],
            'order' => $data['order'],
        ],
    );

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
                'transcripts' => [
                    '*' => [
                        'entity',
                        'media' => [
                            'entity',
                        ],
                    ],
                ],
            ],
        ]);
});

it('allows a member to update a mission session', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $mission = Mission::factory()->create([
        'status' => Approved::class,
    ]);

    $missionSession = MissionSession::factory()->create([
        'mission_id' => $mission->id,
    ]);

    $data = new MissionSessionFactory()->raw();

    // Act
    $response = actingAsTenantUser()->put(
        route('api.mission-sessions.update', [
            'ulid' => $missionSession->ulid,
            'include' => 'facilitator,speaker,classGroup,transcripts.media',
        ]),
        [
            'mission_ulid' => $mission->ulid,
            'facilitator_ulid' => Member::query()->where('id', $data['facilitator_id'])->first()->ulid,
            'speaker_ulid' => Member::query()->where('id', $data['speaker_id'])->first()?->ulid,
            'class_group_ulid' => ClassGroup::query()->where('id', $data['class_group_id'])->first()?->ulid,
            'starts_at' => now()->addDays(2)->toDateTimeString(),
            'ends_at' => now()->addDays(2)->addHours(2)->toDateTimeString(),
            'notes' => $data['notes'],
            'order' => $data['order'],
        ],
    );

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
                'transcripts' => [
                    '*' => [
                        'entity',
                        'media' => [
                            'entity',
                        ],
                    ],
                ],
            ],
        ]);
});

it('enables the deletion of a mission session', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $mission = Mission::factory()->create([
        'status' => Approved::class,
    ]);

    $missionSession = MissionSession::factory()->create([
        'mission_id' => $mission->id,
    ]);

    // Act
    $response = actingAsTenantUser()->delete(route('api.mission-sessions.destroy', [
        'ulid' => $missionSession->ulid,
    ]));

    // Assert
    $response->assertStatus(204);

    expect(MissionSession::find($missionSession->id))->toBeNull();
});
