<?php

use App\Enums\PRFRole;
use App\Filament\Central\Resources\TenantResource;
use App\Filament\Central\Resources\TenantResource\Pages\CreateTenant;
use App\Filament\Central\Resources\TenantResource\Pages\EditTenant;
use App\Jobs\Tenant\ProvisionTenantJob;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    new RolesAndPermissionsSeeder()->run();

    $this->user = User::factory()->create();
    $this->user->assignRole(PRFRole::SUPER_ADMIN);
});

it('can render list tenants page', function () {
    $this->actingAs($this->user);

    $this->get(TenantResource::getUrl('index'))->assertSuccessful();
});

it('can render create tenant page', function () {
    $this->actingAs($this->user);

    $this->get(TenantResource::getUrl('create'))->assertSuccessful();
});

it('can create a tenant', function () {
    $this->actingAs($this->user);

    Bus::fake([ProvisionTenantJob::class]);

    $newTenant = Tenant::factory()->make();

    $this->get(TenantResource::getUrl('create'));

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name' => $newTenant->name,
            'slug' => $newTenant->slug,
            'is_active' => true,
            'admin_email' => 'admin@example.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Tenant::query()->where('data->slug', $newTenant->slug)->first()?->name)->toBe($newTenant->name);

    Bus::assertDispatchedSync(ProvisionTenantJob::class);
});

it('can create a tenant with custom domain', function () {
    $this->actingAs($this->user);

    Bus::fake([ProvisionTenantJob::class]);

    $newTenant = Tenant::factory()->make();

    $this->get(TenantResource::getUrl('create'));

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name' => $newTenant->name,
            'slug' => $newTenant->slug,
            'is_active' => true,
            'custom_domain' => 'custom.example.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $createdTenant = Tenant::where('data->slug', $newTenant->slug)->first();

    expect($createdTenant->domains->pluck('domain'))->toContain('custom.example.com');
});

it('can render view tenant page', function () {
    $this->actingAs($this->user);

    $this->get(TenantResource::getUrl('view', ['record' => $this->tenant]))->assertSuccessful();
});

it('can render edit tenant page', function () {
    $this->actingAs($this->user);

    $this->get(TenantResource::getUrl('edit', ['record' => $this->tenant]))->assertSuccessful();
});

it('can update a tenant', function () {
    $this->actingAs($this->user);

    $this->get(TenantResource::getUrl('edit', ['record' => $this->tenant]));

    Livewire::test(EditTenant::class, ['record' => $this->tenant->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Tenant Name',
            'is_active' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->tenant->fresh()->name)->toBe('Updated Tenant Name');
    expect($this->tenant->fresh()->is_active)->toBeFalse();
});

it('can delete a tenant', function () {
    $this->actingAs($this->user);

    $tenant = Tenant::factory()->create();

    Livewire::test(EditTenant::class, [
        'record' => $tenant->getRouteKey(),
    ])->callAction(DeleteAction::class);

    $this->assertDatabaseMissing(Tenant::class, ['id' => $tenant->id]);
});

it('validates tenant name is required', function () {
    $this->actingAs($this->user);

    $this->get(TenantResource::getUrl('create'));

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name' => null,
            'slug' => 'test-slug',
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

it('validates tenant slug is unique', function () {
    $this->actingAs($this->user);

    $this->get(TenantResource::getUrl('create'));

    Livewire::test(CreateTenant::class)
        ->fillForm([
            'name' => 'Test Tenant',
            'slug' => $this->tenant->slug,
        ])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});
