<?php

namespace Database\Seeders;

use App\Models\MaritalStatus;
use Illuminate\Database\Seeder;

class MaritalStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $maritalStatuses = [
            'Single',
            'Married',
            'Separated',
            'Divorced',
            'Widowed',
        ];

        foreach ($maritalStatuses as $maritalStatus) {
            MaritalStatus::factory()
                ->create([
                    'name' => $maritalStatus,
                ]);
        }
    }
}
