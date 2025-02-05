<?php

namespace Database\Seeders;

use App\Models\MissionGroundSuggestion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MissionGroundSuggestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MissionGroundSuggestion::factory()
            ->count(10)
            ->create();
    }
}
