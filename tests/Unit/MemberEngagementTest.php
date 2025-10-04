<?php

use App\Enums\PRFMissionSubscriptionStatus;
use App\Enums\PRFSoulDecisionType;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionSubscription;
use App\Models\Soul;
use Illuminate\Support\Facades\Artisan;

it('should return member engagement statistics', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Get a member who has some engagement data
    $member = Member::whereHas('missionSubscriptions')->first();

    if (! $member) {
        $member = Member::factory()->create();

        // Create some engagement data
        $mission = Mission::first();
        if ($mission) {
            MissionSubscription::factory()->create([
                'member_id' => $member->id,
                'mission_id' => $mission->id,
                'status' => PRFMissionSubscriptionStatus::APPROVED->value,
            ]);
        }
    }

    // Act
    $response = actingAsUser()->get(route('api.members.engagement', [
        'ulid' => $member->ulid,
    ]));

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'member_ulid',
                'member_name',
                'mission_stats' => [
                    'total_missions',
                    'approved_missions',
                    'mission_streak',
                    'favorite_mission_type',
                    'schools_reached',
                    'mission_roles',
                    'completion_rate',
                ],
                'impact_stats' => [
                    'souls_touched',
                    'decision_types',
                    'most_impactful_mission',
                ],
                'learning_stats' => [
                    'courses_completed',
                    'total_courses_enrolled',
                    'lessons_completed',
                    'learning_progress_percentage',
                    'learning_streak',
                    'favorite_course',
                ],
                'prayer_stats' => [
                    'prayer_responses',
                    'prayer_consistency_days',
                ],
                'event_stats' => [
                    'events_attended',
                    'upcoming_events',
                ],
                'generated_at',
            ],
        ]);

    expect($response->json('data.entity'))->toBe('member-engagement');
    expect($response->json('data.member_ulid'))->toBe($member->ulid);
});

it('should include badges when requested', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $member = Member::whereHas('missionSubscriptions')->first();

    if (! $member) {
        $member = Member::factory()->create();
    }

    // Act
    $response = actingAsUser()->get(route('api.members.engagement', [
        'ulid' => $member->ulid,
        'include_badges' => true,
    ]));

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'badges',
            ],
        ]);
});

it('should include comparative stats when requested', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $member = Member::whereHas('missionSubscriptions')->first();

    if (! $member) {
        $member = Member::factory()->create();
    }

    // Act
    $response = actingAsUser()->get(route('api.members.engagement', [
        'ulid' => $member->ulid,
        'include_comparative_stats' => true,
    ]));

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'comparative_stats',
            ],
        ]);
});

it('should filter engagement by year when provided', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $member = Member::whereHas('missionSubscriptions')->first();

    if (! $member) {
        $member = Member::factory()->create();
    }

    // Act
    $response = actingAsUser()->get(route('api.members.engagement', [
        'ulid' => $member->ulid,
        'year' => now()->year,
    ]));

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'member_ulid',
                'mission_stats',
                'impact_stats',
                'learning_stats',
                'prayer_stats',
                'event_stats',
            ],
        ]);

    expect($response->json('data.entity'))->toBe('member-engagement');
});

it('should return 404 for non-existent member', function () {
    // Act
    $response = actingAsUser()->get(route('api.members.engagement', [
        'ulid' => '01234567890123456789012345',
    ]));

    // Assert
    $response->assertStatus(404);
});

// it('should calculate mission stats correctly', function () {
//     // Setup
//     Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

//     $member = Member::factory()->create();
//     $missions = Mission::take(3)->get();

//     // If we don't have 3 missions, create them
//     if ($missions->count() < 3) {
//         $neededMissions = 3 - $missions->count();
//         for ($i = 0; $i < $neededMissions; $i++) {
//             $missions->push(Mission::factory()->create());
//         }
//     }

//     foreach ($missions as $mission) {
//         MissionSubscription::factory()->create([
//             'member_id' => $member->id,
//             'mission_id' => $mission->id,
//             'status' => PRFMissionSubscriptionStatus::APPROVED->value,
//         ]);
//     }

//     // Verify we created 3 subscriptions
//     $subscriptionCount = MissionSubscription::where('member_id', $member->id)->count();
//     expect($subscriptionCount)->toBe(3, 'Should have created 3 subscriptions in DB');

//     // Act
//     $response = actingAsUser()->get(route('api.members.engagement', [
//         'ulid' => $member->ulid,
//     ]));

//     // Assert
//     $response->assertStatus(200);

//     // Debug output
//     $missionStats = $response->json('data.mission_stats');
//     dump('Mission Stats:', $missionStats);

//     expect($response->json('data.mission_stats.total_missions'))->toBeGreaterThanOrEqual(3, 'Total missions should be >= 3');
//     expect($response->json('data.mission_stats.approved_missions'))->toBeGreaterThanOrEqual(3, 'Approved missions should be >= 3');
// });

it('should calculate impact stats with souls data', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $member = Member::factory()->create();
    $mission = Mission::first();

    if ($mission) {
        MissionSubscription::factory()->create([
            'member_id' => $member->id,
            'mission_id' => $mission->id,
            'status' => PRFMissionSubscriptionStatus::APPROVED->value,
        ]);

        // Create souls for this mission
        Soul::factory()->create([
            'mission_id' => $mission->id,
            'decision_type' => PRFSoulDecisionType::SALVATION->value,
        ]);
    }

    // Act
    $response = actingAsUser()->get(route('api.members.engagement', [
        'ulid' => $member->ulid,
    ]));

    // Assert
    $response->assertStatus(200);

    expect($response->json('data.impact_stats'))->toHaveKey('souls_touched');
    expect($response->json('data.impact_stats'))->toHaveKey('decision_types');
});

it('should require authentication', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
    $member = Member::first();

    // Act
    $response = test()->getJson(route('api.members.engagement', [
        'ulid' => $member->ulid,
    ]));

    // Assert
    $response->assertStatus(401);
});
