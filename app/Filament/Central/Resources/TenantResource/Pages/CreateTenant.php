<?php

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Actions\Tenant\CreateTenantAction;
use App\Filament\Central\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateTenantAction::class)->handle(
            name: $data['name'],
            slug: $data['slug'],
            customDomain: $data['custom_domain'] ?? null,
            shouldProvision: true,
            adminEmail: $data['admin_email'] ?? null,
            adminPassword: $data['admin_password'] ?? '',
        );
    }
}
