<?php

use Illuminate\Support\Facades\Artisan;

it('should return a list of class groups', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'ClassGroupSeeder']);

    // Act
    $response = actingAsUser()->get(route('api.class-groups.index'), [
        'include' => '',
    ]);

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'entity',
                    'ulid',
                    'name',
                    'is_active',
                    'institution_type',
                ],
            ],
        ]);
});
