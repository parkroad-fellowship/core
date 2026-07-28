<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        if (Tenant::count() === 0) {
            Tenant::create([
                'name' => 'New Tenant',
                'slug' => 'default',
                'is_active' => true,
            ]);

            $this->command->info('Default tenant created.');
        }
    }
}
