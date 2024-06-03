<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the super admin user
        $superAdminUserPayload = (new UserFactory())->raw();
        $superAdmin = User::updateOrCreate([
            'email' => 'admin@parkroadfellowship.org',
        ], array_merge($superAdminUserPayload, [
            'email' => 'admin@parkroadfellowship.org',
            'name' => 'Super Admin',
        ]));
        $superAdmin->assignRole('super admin');

        if (App::environment('production')) {
            return;
        }
    }
}
