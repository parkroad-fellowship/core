<?php

namespace App\Actions\Tenant;

use App\Jobs\Tenant\ProvisionTenantJob;
use App\Models\Tenant;

final class CreateTenantAction
{
    public function handle(
        string $name,
        ?string $slug = null,
        ?string $customDomain = null,
        bool $shouldProvision = false,
        ?string $adminEmail = null,
        string $adminPassword = '',
        bool $confirmPromoteExistingAdmin = false,
    ): Tenant {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
        ]);

        if ($customDomain) {
            $tenant->addDomain($customDomain);
        }

        if ($shouldProvision) {
            ProvisionTenantJob::dispatchSync(
                $tenant,
                $adminEmail,
                $adminPassword,
                $confirmPromoteExistingAdmin,
            );
        }

        return $tenant;
    }
}
