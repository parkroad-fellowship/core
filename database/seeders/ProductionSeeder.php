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
            UserSeeder::class,
            SpiritualYearSeeder::class,
            TransferRateSeeder::class,
            ExpenseCategorySeeder::class,
            PaymentTypeSeeder::class,
        ]);
    }
}
