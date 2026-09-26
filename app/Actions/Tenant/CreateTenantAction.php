<?php

namespace App\Actions\Tenant;

use App\Enums\PRFMemberEmailMode;
use App\Jobs\Tenant\ProvisionTenantJob;
use App\Models\Tenant;

final class CreateTenantAction
{
    /**
     * @param  array<string, mixed>  $data  extra tenant configuration stored on the tenant
     */
    public function handle(
        string $name,
        ?string $slug = null,
        ?string $customDomain = null,
        bool $shouldProvision = false,
        ?string $adminEmail = null,
        string $adminPassword = '',
        bool $confirmPromoteExistingAdmin = false,
        PRFMemberEmailMode $memberEmailMode = PRFMemberEmailMode::PERSONAL,
        ?string $orgEmailDomain = null,
        bool $isActive = true,
        array $data = [],
    ): Tenant {
        $tenant = Tenant::create([
            ...$data,
            'name' => $name,
            'slug' => $slug,
            'is_active' => $isActive,
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
                $memberEmailMode,
                $orgEmailDomain,
            );
        }

        return $tenant;
    }
}
