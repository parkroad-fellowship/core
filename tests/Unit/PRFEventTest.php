<?php

use App\Enums\PRFActiveStatus;
use App\Models\Member;
use App\Models\PRFEvent;
use Illuminate\Support\Facades\Artisan;

it('should return a list of events', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route('api.events.index', [
        'include' => 'weatherForecasts,eventSubscriptions',
    ]));

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'entity',
                    'ulid',
                    'name',
                    'description',
                    'start_date',
                    'end_date',
                    'start_time',
                    'end_time',
                    'capacity',
                    'status',
                    'event_subscriptions',
                    'event_subscriptions_needed',
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
});

it('should allow a user to subscribe for a event', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $event = PRFEvent::factory()->create([
        'status' => PRFActiveStatus::ACTIVE,
    ]);

    $member = Member::factory()->create();

    $data = [
        'event_ulid' => $event->ulid,
        'member_ulid' => $member->ulid,
        'number_of_attendees' => 2,
    ];

    // Act
    $response = actingAsUser()->post(
        route('api.event-subscriptions.store', [
            'include' => 'prfEvent,member',
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
                'number_of_attendees',
                'prf_event' => [
                    'entity',
                    'ulid',
                    'name',
                    'description',
                    'start_date',
                    'end_date',
                    'start_time',
                    'end_time',
                    'capacity',
                    'status',
                ],
                'member',
            ],
        ]);
});

it('should allow a user to update an event subscription', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $event = PRFEvent::factory()->create([
        'status' => PRFActiveStatus::ACTIVE,
    ]);

    $member = Member::factory()->create();

    $data = [
        'event_ulid' => $event->ulid,
        'member_ulid' => $member->ulid,
        'number_of_attendees' => 2,
    ];

    $result = actingAsUser()->post(
        route('api.event-subscriptions.store', [
            'include' => 'prfEvent,member',
        ]),
        $data,
    );

    // Act
    $response = actingAsUser()->put(
        route(
            'api.event-subscriptions.update',

            [
                'ulid' => $result->json('data.ulid'),
                'include' => 'prfEvent,member',
            ],
        ),
        [

            'number_of_attendees' => 5,
        ],
    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'number_of_attendees',
                'prf_event' => [
                    'entity',
                    'ulid',
                    'name',
                    'description',
                    'start_date',
                    'end_date',
                    'start_time',
                    'end_time',
                    'capacity',
                    'status',
                ],
                'member',
            ],
        ]);

    expect($response->json('data.number_of_attendees'))->toBe(5);
});

it('should allow for the retrieval of event subscriptions', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route('api.event-subscriptions.index', [
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
                    'number_of_attendees',
                    'member' => [
                        'first_name',
                        'last_name',
                        'phone_number',
                    ],
                ],
            ],
        ]);
});

it('should delete an event subscription', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
    $event = PRFEvent::factory()->create([
        'status' => PRFActiveStatus::ACTIVE,
    ]);
    $member = Member::factory()->create();
    $data = [
        'event_ulid' => $event->ulid,
        'member_ulid' => $member->ulid,
        'number_of_attendees' => 2,
    ];
    $result = actingAsUser()->post(
        route('api.event-subscriptions.store', [
            'include' => 'prfEvent,member',
        ]),
        $data,
    );

    // Act
    $response = actingAsUser()->delete(
        route(
            'api.event-subscriptions.destroy',
            [
                'ulid' => $result->json('data.ulid'),
            ],
        ),
    );
    // Assert
    $response
        ->assertStatus(204);
});
