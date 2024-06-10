<?php

namespace Database\Seeders;

use App\Models\Soul;
use Illuminate\Database\Seeder;

class SoulSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Soul::factory()
            ->count(40)
            ->create();
    }
}
