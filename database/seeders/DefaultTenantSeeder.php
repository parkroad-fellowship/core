<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class DefaultTenantSeeder extends Seeder
{
    public function run(): void
    {
        if (Tenant::count() === 0) {
            $tenant = Tenant::create(['data' => [
                'name' => 'Parkroad Fellowship (PRF)',
            ]]);

            $this->command->info("Default tenant created: {$tenant->id}");
        }
    }
}
