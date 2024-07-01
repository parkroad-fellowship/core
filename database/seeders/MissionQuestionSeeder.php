<?php

namespace Database\Seeders;

use App\Models\MissionQuestion;
use Illuminate\Database\Seeder;

class MissionQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MissionQuestion::factory()
            ->count(40)
            ->create();
    }
}
