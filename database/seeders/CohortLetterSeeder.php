<?php

namespace Database\Seeders;

use App\Models\Cohort;
use App\Models\Letter;
use Illuminate\Database\Seeder;

class CohortLetterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Cohort::cursor() as $cohort) {
            $random = rand(3, 6);
            $cohort->cohortLetters()->createMany(
                Letter::inRandomOrder()->limit($random)->get()->map(function ($letter) {
                    return [
                        'letter_id' => $letter->id,
                    ];
                })->toArray()
            );
        }
    }
}
