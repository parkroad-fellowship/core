<?php

use App\Enums\PRFMissionStatus;
use App\Models\ExpenseCategory;
use App\Models\Member;
use App\Models\Mission;
use Database\Factories\ExpenseFactory;
use Illuminate\Support\Facades\Artisan;

it('should return a list of notes made at debrief sessions', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    // Act
    $response = actingAsUser()->get(route(
        'api.expenses.index',
        [
            'include' => 'expenseCategory,member',
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
                    'channel_type',
                    'charge_type',
                    'expensable_type',
                    'amount',
                    'charge',
                    'confirmation_message',
                    'expense_category',
                    'member',
                ],
            ],
        ]);
});

it('should allow a user to record an expense', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $mission = Mission::factory()->create([
        'status' => PRFMissionStatus::APPROVED,
    ]);

    $data = (new ExpenseFactory)->raw();

    // Act
    $response = actingAsUser()->post(
        route('api.expenses.store', [
            'include' => 'expenseCategory,member',
        ]),
        [
            'expensable_ulid' => $mission->ulid,
            'member_ulid' => Member::query()->find($data['member_id'])->ulid,
            'expense_category_ulid' => ExpenseCategory::query()->find($data['expense_category_id'])->ulid,
            ...$data,
        ],
    );

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [

                'entity',
                'ulid',
                'channel_type',
                'charge_type',
                'expensable_type',
                'amount',
                'charge',
                'confirmation_message',
                'expense_category',
                'member',

            ],
        ]);
});
