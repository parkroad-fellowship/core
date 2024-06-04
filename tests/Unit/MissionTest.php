<?php

use App\Enums\PRFMissionStatus;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionType;
use App\Models\School;
use App\Models\SchoolTerm;
use Illuminate\Support\Facades\Artisan;

it('should return a list of missions', function () {
    // Act
    $response = actingAsUser()->get(route('api.missions.index'));

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
                    'school_term',
                    'mission_type',
                    'school',
                ],
            ],
        ]);
});


it('should allow a member to subscribe for a missions', function () {
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
