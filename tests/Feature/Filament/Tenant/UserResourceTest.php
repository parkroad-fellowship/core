<?php

use App\Actions\Tenant\AddTenantMemberAction;
use App\Filament\Resources\Users\UserResource;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->otherTenant = Tenant::factory()->create();

    $this->tenantUser = User::factory()->create();
    $this->otherTenantUser = User::factory()->create();
    $this->unlinkedUser = User::factory()->create();

    app(AddTenantMemberAction::class)->handle($this->tenant, $this->tenantUser, 'admin');
    app(AddTenantMemberAction::class)->handle($this->otherTenant, $this->otherTenantUser, 'admin');

    initTenancy($this->tenant);
});

it('only returns users linked to the current tenant', function () {
    $userIds = UserResource::getEloquentQuery()->pluck('users.id');

    expect($userIds)
        ->toContain($this->tenantUser->id)
        ->not->toContain($this->otherTenantUser->id)
        ->not->toContain($this->unlinkedUser->id);
});

it('returns all users when tenancy is not initialized', function () {
    tenancy()->end();

    $userIds = UserResource::getEloquentQuery()->pluck('users.id');

    expect($userIds)
        ->toContain($this->tenantUser->id)
        ->toContain($this->otherTenantUser->id)
        ->toContain($this->unlinkedUser->id);
});
