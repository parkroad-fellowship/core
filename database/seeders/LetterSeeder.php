<?php

namespace Database\Seeders;

use App\Models\Letter;
use Illuminate\Database\Seeder;

class LetterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Letter::factory()
            ->count(3)
            ->create();
    }
}
