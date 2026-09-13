<?php

use App\Livewire\PledgeForm;
use App\Models\Pledge;
use App\Models\Tenant;
use Livewire\Livewire;

it('rejects a zero or negative pledge amount on the public form', function () {
    $tenant = Tenant::factory()->create();
    initTenancy($tenant);

    Livewire::test(PledgeForm::class)
        ->set('name', 'Jane Doe')
        ->set('amount', '0')
        ->call('submit')
        ->assertHasErrors(['amount' => 'min']);

    expect(Pledge::query()->count())->toBe(0);
});

it('accepts a positive pledge and shows a confirmation summary with KES currency', function () {
    $tenant = Tenant::factory()->create();
    initTenancy($tenant);

    Livewire::test(PledgeForm::class)
        ->set('name', 'Jane Doe')
        ->set('amount', '50000')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSet('summary.amount', '50,000');

    expect(Pledge::query()->count())->toBe(1)->and(Pledge::query()->first()->amount)->toEqual(50000.0);
});

it('clears the validation error bag when starting a fresh pledge', function () {
    $tenant = Tenant::factory()->create();
    initTenancy($tenant);

    $component = Livewire::test(PledgeForm::class);

    $component->set('amount', '0')->call('submit')->assertHasErrors();

    $component->call('startOver');

    expect($component->errors()->any())->toBeFalse();
});
