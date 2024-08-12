<?php

use App\Models\Member;
use App\Models\PrayerPrompt;
use Illuminate\Support\Facades\Artisan;

it('should allow a user to record whether they have participated in a prayer', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->post(
        route('api.prayer-responses.store', [
            'include' => 'prayerPrompt',
        ]),
        [
            'prayer_prompt_ulid' => PrayerPrompt::query()->inRandomOrder()->first(['ulid'])->ulid,
            'member_ulid' => Member::query()->inRandomOrder()->first(['ulid'])->ulid,
        ],
    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'prayer_prompt',
            ],
        ]);
});
