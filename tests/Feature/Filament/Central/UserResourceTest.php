<?php

use App\Filament\Central\Resources\UserResource;
use App\Filament\Central\Resources\UserResource\Pages\CreateUser;
use App\Filament\Central\Resources\UserResource\Pages\EditUser;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->assignRole('super admin');
});

it('can render list users page', function () {
    $this->actingAs($this->user);

    $this->get(UserResource::getUrl('index'))->assertSuccessful();
});

it('can render create user page', function () {
    $this->actingAs($this->user);

    $this->get(UserResource::getUrl('create'))->assertSuccessful();
});

it('can create a user', function () {
    $this->actingAs($this->user);

    $newUser = User::factory()->make();

    $this->get(UserResource::getUrl('create'));

    $this
        ->livewire(CreateUser::class)
        ->fillForm([
            'name' => $newUser->name,
            'email' => $newUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(User::class, [
        'name' => $newUser->name,
        'email' => $newUser->email,
    ]);
});

it('can render view user page', function () {
    $this->actingAs($this->user);

    $this->get(UserResource::getUrl('view', ['record' => $this->user]))->assertSuccessful();
});

it('can render edit user page', function () {
    $this->actingAs($this->user);

    $this->get(UserResource::getUrl('edit', ['record' => $this->user]))->assertSuccessful();
});

it('can update a user', function () {
    $this->actingAs($this->user);

    $this->get(UserResource::getUrl('edit', ['record' => $this->user]));

    $this
        ->livewire(EditUser::class, ['record' => $this->user->getRouteKey()])
        ->fillForm([
            'name' => 'Updated User Name',
            'email' => 'updated@example.com',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->user->fresh()->name)->toBe('Updated User Name');
    expect($this->user->fresh()->email)->toBe('updated@example.com');
});

it('can delete a user', function () {
    $this->actingAs($this->user);

    $userToDelete = User::factory()->create();

    $this->livewire(EditUser::class, [
        'record' => $userToDelete->getRouteKey(),
    ])->callAction(\Filament\Actions\DeleteAction::class);

    $this->assertDatabaseMissing(User::class, ['id' => $userToDelete->id]);
});

it('validates user name is required', function () {
    $this->actingAs($this->user);

    $this->get(UserResource::getUrl('create'));

    $this
        ->livewire(CreateUser::class)
        ->fillForm([
            'name' => null,
            'email' => 'test@example.com',
            'password' => 'password123',
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

it('validates user email is unique', function () {
    $this->actingAs($this->user);

    $this->get(UserResource::getUrl('create'));

    $this
        ->livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Test User',
            'email' => $this->user->email,
            'password' => 'password123',
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique']);
});

it('validates user password is required on create', function () {
    $this->actingAs($this->user);

    $this->get(UserResource::getUrl('create'));

    $this
        ->livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['password' => 'required']);
});
