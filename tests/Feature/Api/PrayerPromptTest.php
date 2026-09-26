<?php

use Illuminate\Support\Facades\Artisan;

it('returns a list of prayer prompts for use by clients', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsTenantUser()->get(route('api.prayer-prompts.index'));

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
