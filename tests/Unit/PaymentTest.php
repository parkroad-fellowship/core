<?php

use Illuminate\Support\Facades\Artisan;

it('should return a list of payments', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route('api.payments.index', [
        'include' => 'member,paymentType',
    ]));

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'entity',
                    'ulid',
                    'amount',
                    'payment_status',
                    'reference',
                    'member' => [
                        'ulid',
                    ],
                    'payment_type' => [
                        'ulid',
                    ],
                ],
            ],
        ]);
});
