<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SpeakerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Speaker::factory()
            ->count(10)
            ->create();
    }
}
