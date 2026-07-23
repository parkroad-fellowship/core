<?php

namespace App\Actions\Tenant;

use App\Helpers\Utils;
use App\Jobs\Tenant\ProvisionTenantJob;
use App\Models\Tenant;

final class CreateTenantAction
{
    public function handle(
        string $name,
        ?string $slug = null,
        ?string $customDomain = null,
        bool $shouldSeedDomains = true,
        bool $shouldProvision = false,
        ?string $adminEmail = null,
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

        if ($shouldSeedDomains) {
            Utils::seedDomains($tenant->id);
        }

        if ($shouldProvision) {
            ProvisionTenantJob::dispatchSync(
                $tenant,
                $adminEmail,
                $confirmPromoteExistingAdmin,
            );
        }

        return $tenant;
    }
}
