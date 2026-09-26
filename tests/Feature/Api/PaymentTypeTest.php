<?php

use App\Models\PaymentType;
use Illuminate\Support\Facades\Artisan;

it('returns a list of payment types', function () {
    // Setup
    Artisan::call('db:seed', ['--class' => 'PaymentTypeSeeder']);

    // Act
    $response = actingAsTenantUser()->get(route('api.payment-types.index'), [
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

it('creates a payment type', function () {
    $response = actingAsTenantUser()->postJson(route('api.payment-types.store'), [
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

it('shows a payment type', function () {
    $paymentType = PaymentType::factory()->create();

    $response = actingAsTenantUser()->getJson(route('api.payment-types.show', $paymentType->ulid));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.ulid', $paymentType->ulid)
        ->assertJsonPath('data.name', $paymentType->name);
});

it('updates a payment type', function () {
    $paymentType = PaymentType::factory()->create();

    $response = actingAsTenantUser()->putJson(route('api.payment-types.update', $paymentType->ulid), [
        'name' => 'Updated Name',
    ]);

    $response->assertSuccessful()->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('payment_types', [
        'ulid' => $paymentType->ulid,
        'name' => 'Updated Name',
    ]);
});

it('deletes a payment type', function () {
    $paymentType = PaymentType::factory()->create();

    $response = actingAsTenantUser()->deleteJson(route('api.payment-types.destroy', $paymentType->ulid));

    $response->assertStatus(204);

    $this->assertSoftDeleted('payment_types', [
        'ulid' => $paymentType->ulid,
    ]);
});

it('validates required fields when creating a payment type', function () {
    $response = actingAsTenantUser()->postJson(route('api.payment-types.store'), []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['name', 'description']);
});
