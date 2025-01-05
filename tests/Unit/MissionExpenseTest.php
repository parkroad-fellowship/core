<?php

use App\Models\Mission;
use Illuminate\Support\Facades\Artisan;

it('should return a list of mission expenses', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route('api.mission-expenses.index', [
        'include' => 'mission',
    ]));

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'entity',
                    'ulid',
                    'amount_received',
                    'token_amount',
                    'amount_to_refund',
                    'amount_refunded',
                    'is_refunded',
                    'mission',
                ],
            ],
        ]);
});

it('should return a single mission expense by the mission ulid', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $mission = Mission::first();

    // Act
    $response = actingAsUser()->get(
        route(
            'api.mission-expenses.show',
            [
                'ulid' => $mission->ulid,
                'include' => 'mission,expenses.expenseCategory',
            ]
        ),

    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'amount_received',
                'token_amount',
                'amount_to_refund',
                'amount_refunded',
                'is_refunded',
                'mission',
                'expenses' => [
                    '*' => [
                        'entity',
                        'ulid',
                        'amount',
                        'expense_category',
                    ],
                ],
            ],
        ]);
});
