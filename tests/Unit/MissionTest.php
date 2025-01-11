<?php

use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Member;
use App\Models\Mission;
use Illuminate\Support\Facades\Artisan;

it('should return a list of missions', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route('api.missions.index', [
        'include' => 'school,schoolTerm,missionType,missionSubscriptions,school.schoolContacts.contactType,missionExpense.expenses,weatherForecasts',
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
                    'capacity',
                    'status',
                    'mission_prep_notes',
                    'school' => [
                        'entity',
                        'ulid',
                        'name',
                        'school_contacts',
                    ],
                    'school_term',
                    'mission_type',
                    'school',
                    'mission_subscriptions',
                    'mission_expense' => [
                        'entity',
                        'ulid',
                        'expenses',
                    ],
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
                            'forecast_data',
                        ],
                    ],
                ],
            ],
        ]);
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

    $member = Member::factory()->create();

    $data = [
        'mission_ulid' => $mission->ulid,
        'member_ulid' => $member->ulid,
    ];

    $result = actingAsUser()->post(
        route('api.mission-subscriptions.store', [
            'include' => 'mission.school,mission.schoolTerm,mission.missionType,member',
        ]),
        $data,
    );

    // Act
    $response = actingAsUser()->put(
        route(
            'api.mission-subscriptions.update',

            [
                'missionSubscriptionUlid' => $result->json('data.ulid'),
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
                        'phone_number',
                    ],
                ],
            ],
        ]);
});
