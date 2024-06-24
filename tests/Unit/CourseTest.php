<?php

use App\Models\Group;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

it('should return a list of courses', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $groups = Group::query()->select('ulid')->inRandomOrder()->limit(3)->get();

    // Act
    $response = actingAsUser()->get(route(
        'api.courses.index',
        [
            'include' => 'thumbnail,courseMember',
            'filter[groups]' => [],
        ]
    ));

    Log::info($response->json());

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'entity',
                    'ulid',
                    'name',
                    'slug',
                    'description',
                    'is_active',
                    'thumbnail',
                    'course_member',
                ],
            ],
        ]);
});
