<?php

namespace Database\Seeders;

use App\Console\Commands\Course\UploadMissionPolicyContentCommand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class ProductionSeeder extends Seeder
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
            UserSeeder::class,
            SpiritualYearSeeder::class,
            TransferRateSeeder::class,
            ExpenseCategorySeeder::class,
            MaritalStatusSeeder::class,

            ContactTypeSeeder::class,
            SchoolSeeder::class,

            SchoolTermSeeder::class,
            MissionTypeSeeder::class,
            PaymentTypeSeeder::class,
            ClassGroupSeeder::class,

            MissionSeeder::class,

            AnnouncementSeeder::class,

            PrayerPromptSeeder::class,

            PRFEventSeeder::class,

            GroupSeeder::class,

            MissionFaqCategorySeeder::class,
            MissionFaqSeeder::class,
        ]);

        Artisan::call(UploadMissionPolicyContentCommand::class);
    }
}
