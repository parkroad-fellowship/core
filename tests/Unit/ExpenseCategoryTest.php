<?php

use App\Enums\PRFActiveStatus;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Artisan;

it('should return a list of expense categories', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'ExpenseCategorySeeder']);

    // Act
    $response = actingAsTenantUser()->get(route('api.expense-categories.index'), [
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

it('shows a single expense category', function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $expenseCategory = ExpenseCategory::query()->create([
        'name' => 'Travel',
        'description' => 'Travel and logistics',
        'is_active' => PRFActiveStatus::ACTIVE,
    ]);

    $response = actingAsTenantUser()->get(route('api.expense-categories.show', [
        'ulid' => $expenseCategory->ulid,
        'include' => 'expenses',
    ]));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.ulid', $expenseCategory->ulid)
        ->assertJsonPath('data.name', 'Travel')
        ->assertJsonPath('data.description', 'Travel and logistics');
});

it('creates an expense category', function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $payload = [
        'name' => 'Logistics',
        'description' => 'Transportation and logistics expenses',
        'is_active' => PRFActiveStatus::ACTIVE,
    ];

    $response = actingAsTenantUser()->post(route('api.expense-categories.store', [
        'include' => 'expenses',
    ]), $payload);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Logistics')
        ->assertJsonPath('data.description', 'Transportation and logistics expenses')
        ->assertJsonPath('data.is_active', PRFActiveStatus::ACTIVE);

    $this->assertDatabaseHas('expense_categories', [
        'name' => 'Logistics',
        'description' => 'Transportation and logistics expenses',
        'is_active' => PRFActiveStatus::ACTIVE,
    ]);
});

it('updates an expense category', function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $expenseCategory = ExpenseCategory::query()->create([
        'name' => 'Supplies',
        'description' => 'Office supplies',
        'is_active' => PRFActiveStatus::ACTIVE,
    ]);

    $response = actingAsTenantUser()->patch(
        route('api.expense-categories.update', [
            'ulid' => $expenseCategory->ulid,
            'include' => 'expenses',
        ]),
        [
            'name' => 'Office Supplies',
            'description' => 'General office supplies and stationery',
            'is_active' => PRFActiveStatus::INACTIVE,
        ],
    );

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Office Supplies')
        ->assertJsonPath('data.description', 'General office supplies and stationery')
        ->assertJsonPath('data.is_active', PRFActiveStatus::INACTIVE);

    $this->assertDatabaseHas('expense_categories', [
        'id' => $expenseCategory->id,
        'name' => 'Office Supplies',
        'description' => 'General office supplies and stationery',
        'is_active' => PRFActiveStatus::INACTIVE,
    ]);
});

it('soft deletes an expense category', function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);

    $expenseCategory = ExpenseCategory::query()->create([
        'name' => 'Utilities',
        'description' => 'Utility bills',
        'is_active' => PRFActiveStatus::ACTIVE,
    ]);

    $response = actingAsTenantUser()->delete(route('api.expense-categories.destroy', [
        'ulid' => $expenseCategory->ulid,
    ]));

    $response->assertStatus(204);

    $this->assertSoftDeleted('expense_categories', [
        'id' => $expenseCategory->id,
    ]);
});
