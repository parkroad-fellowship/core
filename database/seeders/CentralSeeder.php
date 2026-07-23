<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CentralSeeder extends Seeder
{
    /**
     * Seed the central/global context: default tenant, roles, and super admin user.
     *
     * Usage: php artisan db:seed --class=CentralSeeder
     */
    public function run(): void
    {
        $this->call(CentralSettingSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(UserSeeder::class);
    }
}
