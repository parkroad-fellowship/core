<?php

use App\Models\Group;
use Illuminate\Support\Facades\Artisan;

it('should return a list of announcements by the OS', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $groups = Group::query()->select('ulid')->inRandomOrder()->limit(3)->get();

    // Act
    $response = actingAsUser()->get(route(
        'api.announcements.index',
        [
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
                    'title',
                    'content',
                    'created_at',
                    'updated_at',
                    'published_at',
                ],
            ],
        ]);
});
