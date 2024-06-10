<?php

namespace Database\Seeders;

use App\Models\DebriefNote;
use Illuminate\Database\Seeder;

class DebriefNoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DebriefNote::factory()
            ->count(40)
            ->create();
    }
}
