<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpiritualYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $spiritualYears = [
            ['name' => '2023 - 2024'],
            ['name' => '2024 - 2025'],
            ['name' => '2025 - 2026'],
            ['name' => '2026 - 2027'],
            ['name' => '2027 - 2028'],
            ['name' => '2028 - 2029'],
            ['name' => '2029 - 2030'],
            ['name' => '2030 - 2031'],
        ];

        foreach ($spiritualYears as $spiritualYear) {
            \App\Models\SpiritualYear::updateOrCreate($spiritualYear);
        }
    }
}
