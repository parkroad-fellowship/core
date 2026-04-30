<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            APIClientSeeder::class,
            AppSettingSeeder::class,
            SpiritualYearSeeder::class,
            TransferRateSeeder::class,
            ExpenseCategorySeeder::class,
            GroupSeeder::class,
            ChatBotSeeder::class,
        ]);

        $this->call([
            ChurchSeeder::class,
            MaritalStatusSeeder::class,
            ProfessionSeeder::class,
            GiftSeeder::class,
            DepartmentSeeder::class,
            ClassGroupSeeder::class,
        ]);

        $this->call([
            UserSeeder::class,
            MemberSeeder::class,
            MembershipSeeder::class,
        ]);

        $this->call([
            ContactTypeSeeder::class,
            SchoolSeeder::class,
            // BudgetEstimateSeeder::class,
        ]);

        $this->call([
            SchoolTermSeeder::class,
            MissionTypeSeeder::class,
        ]);

        $this->call([
            MissionSeeder::class,
            // SoulSeeder::class,
            // DebriefNoteSeeder::class,
            // MissionQuestionSeeder::class,
            MissionFaqCategorySeeder::class,
            MissionFaqSeeder::class,
        ]);

        $this->call([
            CourseModuleSeeder::class,
            LessonModuleSeeder::class,
        ]);

        $this->call([
            CourseGroupSeeder::class,
        ]);

        $this->call([
            LetterSeeder::class,
            // CohortSeeder::class,
            CohortMissionSeeder::class,
            CohortLetterSeeder::class,
        ]);

        $this->call([
            StudentSeeder::class,
            StudentEnquirySeeder::class,
            StudentEnquiryReplySeeder::class,
        ]);

        $this->call([
            AnnouncementSeeder::class,
            AnnouncementGroupSeeder::class,
        ]);

        $this->call([
            PrayerPromptSeeder::class,
            MissionGroundSuggestionSeeder::class,
        ]);

        $this->call([
            PaymentTypeSeeder::class,
            PaymentSeeder::class,
        ]);

        $this->call([
            PRFEventSeeder::class,
        ]);

        $this->call([
            PrayerRequestSeeder::class,
        ]);

        $this->call([
            SpeakerSeeder::class,
        ]);

    }
}
