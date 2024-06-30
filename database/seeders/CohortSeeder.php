<?php

namespace Database\Seeders;

use App\Models\Cohort;
use Illuminate\Database\Seeder;

class CohortSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cohort::factory()
            ->count(10)
            ->create();
    }
}
