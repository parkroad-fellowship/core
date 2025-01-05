<?php

use Illuminate\Support\Facades\Artisan;

it('should return a list of expense categories', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'ExpenseCategorySeeder']);

    // Act
    $response = actingAsUser()->get(route('api.expense-categories.index'), [
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
