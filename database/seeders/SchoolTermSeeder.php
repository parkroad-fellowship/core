<?php

namespace Database\Seeders;

use App\Models\SchoolTerm;
use Illuminate\Database\Seeder;

class SchoolTermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schoolTerms = [
            [
                'name' => 'First Term',
                'year' => 2026,
            ],
            [
                'name' => 'Second Term',
                'year' => 2026,
            ],
            [
                'name' => 'Third Term',
                'year' => 2026,
            ],
        ];

        foreach ($schoolTerms as $schoolTerm) {
            SchoolTerm::create($schoolTerm);
        }
    }
}
