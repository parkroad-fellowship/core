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
        $professions = [
            'Student',
            'Searching',
            'Lawyer',
            'Accountant',
        ];

        foreach ($professions as $profession) {
            Profession::factory()
                ->create([
                    'name' => $profession,
                ]);
        }
    }
}
