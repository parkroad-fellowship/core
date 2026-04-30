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
            [
                'name' => config('prf.app.global_group', 'All'),
                'description' => 'All members and friends',
            ],
            [
                'name' => 'PRF Network',
                'description' => 'Senior members',
            ],
            [
                'name' => 'BSF',
                'description' => 'The brothers and sisters fellowship. Meant for single people.',
            ],
            [
                'name' => 'Young Couples',
                'description' => 'Meant for couples',
            ],
            [
                'name' => '3G/4G',
                'description' => 'Meant for 3G/4G',
            ],
            [
                'name' => 'Older Parkroaders',
                'description' => 'Meant for older parkroaders',
            ],
        ];

        foreach ($groups as $group) {
            Group::updateOrCreate([
                'name' => $group['name'],
                'description' => $group['description'],
                'official_whatsapp_link' => 'https://',
            ]);
        }
    }
}
