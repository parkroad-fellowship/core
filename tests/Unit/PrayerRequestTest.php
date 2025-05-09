<?php

use App\Models\Member;
use Database\Factories\PrayerRequestFactory;
use Illuminate\Support\Facades\Artisan;

it('should return a list of prayer requests', function () {

    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route('api.prayer-requests.index', [
        'include' => 'member',
    ]));
    // Assert
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'entity',
                'ulid',
                'title',
                'description',
                'member' => [
                    'ulid',
                ],
            ],
        ],
    ]);
});

it('should create a prayer request', function () {

    // Setup
    Artisan::Call('db:seed', ['--class' => 'DatabaseSeeder']);

    $member = Member::factory()->create();
    $data = (new PrayerRequestFactory)->raw();

    // Act
    $response = actingAsUser()->post(
        route('api.prayer-requests.store', []),
        [
            'title' => $data['title'],
            'description' => $data['description'],
            'member_ulid' => Member::find($data['member_id'])->ulid,
        ]
    );

    // Assert
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'entity',
            'ulid',
            'title',
            'description',
        ],
    ]);
});
