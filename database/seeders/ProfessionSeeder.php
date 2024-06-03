<?php

namespace Database\Seeders;

use App\Models\Profession;
use Illuminate\Database\Seeder;

class ProfessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            Profession::factory()->create(10);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
