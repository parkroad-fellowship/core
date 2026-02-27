<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// To be run in production for the first time
class InitialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            APIClientSeeder::class,
            AppSettingSeeder::class,
            MaritalStatusSeeder::class,
            ProfessionSeeder::class,
            ChurchSeeder::class,
            UserSeeder::class,
            SpiritualYearSeeder::class,
            TransferRateSeeder::class,
            ExpenseCategorySeeder::class,
            PaymentTypeSeeder::class,
            MissionFaqCategorySeeder::class,
            MissionFaqSeeder::class,
            GroupSeeder::class,
        ]);
    }
}
