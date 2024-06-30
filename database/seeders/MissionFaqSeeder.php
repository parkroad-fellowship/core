<?php

namespace Database\Seeders;

use App\Models\MissionFaq;
use Illuminate\Database\Seeder;

class MissionFaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MissionFaq::factory()
            ->count(15)
            ->create();
    }
}
