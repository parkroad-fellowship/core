<?php

use Illuminate\Support\Facades\Artisan;

it('should return a list of payment types', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'PaymentTypeSeeder']);

    // Act
    $response = actingAsUser()->get(route('api.payment-types.index'), [
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
                    'description',
                    'is_active',
                ],
            ],
        ]);
});
