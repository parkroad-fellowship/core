<?php

namespace Database\Seeders;

use App\Models\CentralSetting;
use Illuminate\Database\Seeder;

class CentralSettingSeeder extends Seeder
{
    public function run(): void
    {
        CentralSetting::set('admin_emails', [], 'admin', 'array');

        $this->command->info('Central settings seeded with admin_emails');
    }
}
