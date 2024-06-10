<?php

use Illuminate\Support\Facades\Artisan;

it('should return a list of class groups', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route(
        'api.souls.index',
        [
            'include' => 'mission,classGroup',
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
                    'full_name',
                    'mission',
                    'class_group'
                ],
            ],
        ]);
});
