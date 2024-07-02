<?php

use App\Models\Group;
use Illuminate\Support\Facades\Artisan;

it('should return a list of announcements by the OS', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $groups = Group::query()->select('ulid')->inRandomOrder()->limit(3)->get();

    // Act
    $response = actingAsUser()->get(route(
        'api.announcement-groups.index',
        [
            'include' => 'announcement',
            'filter[group_ulids]' => $groups->pluck('ulid')->join(','),
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
                    'created_at',
                    'updated_at',
                    'announcement' => [
                        'entity',
                        'ulid',
                        'title',
                        'content',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
        ]);
});
