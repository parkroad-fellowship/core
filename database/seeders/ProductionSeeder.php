<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SpiritualYearSeeder::class,
            MpesaRateSeeder::class,
        ]);
    }
}
