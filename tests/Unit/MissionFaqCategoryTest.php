<?php

use Illuminate\Support\Facades\Artisan;

it('should return a list of faqs categories', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route(
        'api.mission-faq-categories.index',
        []
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
                ],
            ],
        ]);
});
