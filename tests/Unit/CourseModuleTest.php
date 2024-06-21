<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

it('should return a list of course modules', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route(
        'api.course-modules.index',
        [
            'include' => 'course.thumbnail,module.thumbnail,module.lessonModules.lesson',
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
                    'order',
                    'created_at',
                    'updated_at',
                    'course' => [
                        'entity',
                        'ulid',
                        'name',
                        'slug',
                        'description',
                        'is_active',
                        'thumbnail',
                    ],
                    'module' => [
                        'entity',
                        'ulid',
                        'name',
                        'slug',
                        'description',
                        'is_active',
                        'thumbnail',
                        'lessonModules' => [
                            '*' => [
                                'entity',
                                'ulid',
                                'order',
                                'lesson' => [
                                    'entity',
                                    'ulid',
                                    'name',
                                    'slug',
                                    'description',
                                    'is_active',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
});
