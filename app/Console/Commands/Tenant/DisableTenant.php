<?php

namespace App\Console\Commands\Tenant;

use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use Illuminate\Console\Command;

class DisableTenant extends Command
{
    protected $signature = 'tenants:disable {tenant : Tenant slug}';

    protected $description = 'Disable a tenant and revoke all its tokens';

    public function handle(): int
    {
        $tenant = Tenant::where('data->slug', $this->argument('tenant'))->firstOrFail();
        $tenant->update(['is_active' => false]);

        tenancy()->initialize($tenant);

        try {
            PersonalAccessToken::query()
                ->where('tenant_id', $tenant->id)
                ->delete();
        } finally {
            tenancy()->end();
        }

        $this->info("Tenant '{$tenant->name}' has been disabled.");

        return self::SUCCESS;
    }
}
