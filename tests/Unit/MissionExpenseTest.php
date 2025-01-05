<?php

use App\Models\Mission;
use App\Models\MissionExpense;
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
                'amount_spent',
                'token_amount',
                'amount_to_refund',
                'amount_refunded',
                'is_refunded',
                'balance',
                'mission',
                'expenses' => [
                    '*' => [
                        'entity',
                        'ulid',
                        'unit_cost',
                        'expense_category',
                    ],
                ],
            ],
        ]);
});


it('should allow a member to update the token they have received from the mission', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $missionExpense = MissionExpense::first();

    $data = [
        'token_amount' => 1000,
    ];
    // Act
    $response = actingAsUser()->put(
        route(
            'api.mission-expenses.update',
            [
                'ulid' => $missionExpense->ulid,

            ],
        ),
        [
            
            'token_amount' => $data['token_amount'],
        ],
    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'token_amount',
            ],
        ]);

    expect($response->json('data.token_amount'))->not->toBe($missionExpense->token_amount);
    expect($response->json('data.token_amount'))->toBe($data['token_amount']);
});
