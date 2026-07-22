<?php

namespace Database\Seeders;

use App\Helpers\Utils;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class DefaultTenantSeeder extends Seeder
{
    public function run(): void
    {
        if (Tenant::count() === 0) {
            $tenantId = Utils::getOrCreateDefaultTenant();

            Utils::seedDomains($tenantId);

            $this->command->info("Default tenant created: {$tenantId}");
        }
    }
}
