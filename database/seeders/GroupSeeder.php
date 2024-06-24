<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            'BSF',
            'Young Couples',
            '3G/4G',
            'Older Parkroaders',
        ];

        foreach ($groups as $group) {
            Group::factory()
                ->create([
                    'name' => $group,
                ]);
        }
    }
}
