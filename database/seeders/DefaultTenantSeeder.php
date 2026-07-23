<?php

namespace Database\Seeders;

use App\Actions\Tenant\CreateTenantAction;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class DefaultTenantSeeder extends Seeder
{
    public function run(): void
    {
        if (Tenant::count() === 0) {
            $tenant = app(CreateTenantAction::class)->handle(
                name: 'Parkroad Fellowship',
                shouldProvision: false,
            );

            $this->command->info("Default tenant created: {$tenant->id}");
        }
    }
}
