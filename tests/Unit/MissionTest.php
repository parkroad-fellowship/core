<?php

use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Member;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

it('should return a list of missions', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route('api.missions.index', [
        'include' => 'school,schoolTerm,missionType,missionSubscriptions,school.schoolContacts.contactType,weatherForecasts',
    ]));

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'entity',
                    'ulid',
                    'start_date',
                    'end_date',
                    'start_time',
                    'end_time',
                    'capacity',
                    'status',
                    'mission_prep_notes',
                    'whats_app_link',
                    'dressing_recommendations',
                    'activity_recommendations',
                    'mission_subscriptions_needed',
                    'school' => [
                        'entity',
                        'ulid',
                        'name',
                        'school_contacts',
                        'distance',
                        'static_duration',
                    ],
                    'school_term',
                    'mission_type',
                    'school',
                    'mission_subscriptions',
                    'weather_forecasts' => [
                        '*' => [
                            'entity',
                            'ulid',
                            'forecast_date',
                            'weather_code',
                            'weather_code_description',
                            'moon_rise_time',
                            'moon_set_time',
                            'sun_rise_time',
                            'sun_set_time',
                            'cloud_cover',
                            'dew_point',
                            'humidity',
                            'precipitation_probability',
                            'rain',
                            'temperature',
                            'uv',
                            'visibility',
                            'wind',

                            'dressing_recommendations',
                            'activity_recommendations',
                        ],
                    ],
                ],
            ],
        ]);

    collect($response->json('data'))->each(function (array $mission): void {
        expect($mission['school'])->not->toBeNull();
        expect($mission['school_term'])->not->toBeNull();
        expect($mission['mission_type'])->not->toBeNull();
    });

    expect($response->json('data.0.start_time'))->toMatch('/^\d{2}:\d{2}$/');
    expect($response->json('data.0.end_time'))->toMatch('/^\d{2}:\d{2}$/');
});

it('should allow a user to subscribe for a mission', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $mission = Mission::factory()->create([
        'status' => PRFMissionStatus::APPROVED,
    ]);

    $member = Member::factory()->create();

    $data = [
        'mission_ulid' => $mission->ulid,
        'member_ulid' => $member->ulid,
    ];

    // Act
    $response = actingAsUser()->post(
        route('api.mission-subscriptions.store', [
            'include' => 'mission.school,mission.schoolTerm,mission.missionType,member',
        ]),
        $data,
    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'status',
                'mission' => [
                    'entity',
                    'ulid',
                    'start_date',
                    'end_date',
                    'start_time',
                    'end_time',
                    'capacity',
                    'status',
                    'mission_prep_notes',
                    'school_term' => [
                        'entity',
                        'ulid',
                        'name',
                        'year',
                    ],
                    'mission_type' => [
                        'entity',
                        'ulid',
                        'name',
                    ],
                    'school' => [
                        'entity',
                        'ulid',
                        'name',
                    ],
                ],
                'member',
            ],
        ]);
});

it('should allow a user to update a mission subscription', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $mission = Mission::factory()->create([
        'status' => PRFMissionStatus::APPROVED,
    ]);

    $user = User::factory()->create();
    $user->assignRole('member');
    $member = Member::factory()->create(['user_id' => $user->id]);

    $data = [
        'mission_ulid' => $mission->ulid,
        'member_ulid' => $member->ulid,
    ];

    $actor = actingAsStaticUser($user);

    $result = $actor->post(
        route('api.mission-subscriptions.store', [
            'include' => 'mission.school,mission.schoolTerm,mission.missionType,member',
        ]),
        $data,
    );

    // Act
    $response = $actor->put(
        route(
            'api.mission-subscriptions.update',

            [
                'ulid' => $result->json('data.ulid'),
                'include' => 'mission.school,mission.schoolTerm,mission.missionType,member',
            ],
        ),
        [

            'status' => PRFMissionSubscriptionStatus::WITHDRAWN->value,
        ],
    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'status',
                'mission' => [
                    'entity',
                    'ulid',
                    'start_date',
                    'end_date',
                    'start_time',
                    'end_time',
                    'capacity',
                    'status',
                    'mission_prep_notes',
                    'school_term' => [
                        'entity',
                        'ulid',
                        'name',
                        'year',
                    ],
                    'mission_type' => [
                        'entity',
                        'ulid',
                        'name',
                    ],
                    'school' => [
                        'entity',
                        'ulid',
                        'name',
                    ],
                ],
                'member',
            ],
        ]);

    expect($response->json('data.status'))->toBe(PRFMissionSubscriptionStatus::WITHDRAWN->value);
});

it('should allow for the retrieval of mission subscriptions', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route('api.mission-subscriptions.index', [
        'include' => 'member',
    ]), );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'entity',
                    'ulid',
                    'status',
                    'member' => [
                        'first_name',
                        'last_name',
                    ],
                ],
            ],
        ]);
});

it('should ensure start_time and end_time are formatted as time strings', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
    $mission = Mission::factory()->create();

    // Assert that the time fields are properly formatted as HH:MM
    expect($mission->start_time)->toMatch('/^\d{2}:\d{2}$/');
    expect($mission->end_time)->toMatch('/^\d{2}:\d{2}$/');

    // Verify they are valid time strings that can be parsed
    expect(strtotime($mission->start_time))->not->toBeFalse();
    expect(strtotime($mission->end_time))->not->toBeFalse();

    // Verify the times are different from full datetime strings
    expect($mission->start_time)->not->toContain(' ');
    expect($mission->end_time)->not->toContain(' ');
});

it('should accept manual time string assignments', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
    $mission = Mission::factory()->create([
        'start_time' => '09:30',
        'end_time' => '15:45',
    ]);

    // Assert
    expect($mission->start_time)->toBe('09:30');
    expect($mission->end_time)->toBe('15:45');

    // Verify they remain as time strings when retrieved
    $retrievedMission = Mission::find($mission->id);
    expect($retrievedMission->start_time)->toBe('09:30');
    expect($retrievedMission->end_time)->toBe('15:45');
});
