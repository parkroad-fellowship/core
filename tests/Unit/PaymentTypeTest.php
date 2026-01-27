<?php

use App\Models\PaymentType;
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

it('should create a payment type', function () {
    $response = actingAsUser()->postJson(route('api.payment-types.store'), [
        'name' => 'Cash',
        'description' => 'Cash payment',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'entity',
                'ulid',
                'name',
                'description',
                'is_active',
            ],
        ])
        ->assertJsonPath('data.name', 'Cash')
        ->assertJsonPath('data.description', 'Cash payment');

    $this->assertDatabaseHas('payment_types', [
        'name' => 'Cash',
        'description' => 'Cash payment',
    ]);
});

it('should show a payment type', function () {
    $paymentType = PaymentType::factory()->create();

    $response = actingAsUser()->getJson(route('api.payment-types.show', $paymentType->ulid));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.ulid', $paymentType->ulid)
        ->assertJsonPath('data.name', $paymentType->name);
});

it('should update a payment type', function () {
    $paymentType = PaymentType::factory()->create();

    $response = actingAsUser()->putJson(route('api.payment-types.update', $paymentType->ulid), [
        'name' => 'Updated Name',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('payment_types', [
        'ulid' => $paymentType->ulid,
        'name' => 'Updated Name',
    ]);
});

it('should delete a payment type', function () {
    $paymentType = PaymentType::factory()->create();

    $response = actingAsUser()->deleteJson(route('api.payment-types.destroy', $paymentType->ulid));

    $response->assertStatus(204);

    $this->assertSoftDeleted('payment_types', [
        'ulid' => $paymentType->ulid,
    ]);
});

it('should validate required fields when creating a payment type', function () {
    $response = actingAsUser()->postJson(route('api.payment-types.store'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'description']);
});
