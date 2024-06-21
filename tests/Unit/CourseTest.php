<?php

use Illuminate\Support\Facades\Artisan;

it('should return a list of courses', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route(
        'api.courses.index',
        [
            'include' => 'thumbnail',
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
                    'name',
                    'slug',
                    'description',
                    'is_active',
                    'thumbnail',
                ],
            ],
        ]);
});
