<?php

use Illuminate\Support\Facades\Artisan;

it('should return a list of prayer prompts for use by clients', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route(
        'api.prayer-prompts.index',
    ));

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'entity',
                    'ulid',
                    'description',
                    'frequency',
                    'day_of_week',
                    'time_of_day',
                    'is_active',
                ],
            ],
        ]);
});
