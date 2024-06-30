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
        ]);

        $this->call([
            ContactTypeSeeder::class,
            SchoolSeeder::class,
        ]);

        $this->call([
            SchoolTermSeeder::class,
            MissionTypeSeeder::class,
        ]);

        $this->call([
            MissionSeeder::class,
            SoulSeeder::class,
            DebriefNoteSeeder::class,
            MissionQuestionSeeder::class,
            MissionFaqSeeder::class,
        ]);

        $this->call([
            // CourseSeeder::class,
            // ModuleSeeder::class,
            // LessonSeeder::class,
            CourseWorkSeeder::class,
            CourseModuleSeeder::class,
            LessonModuleSeeder::class,
            // CourseProgressSeeder::class,
        ]);

        $this->call([
            GroupSeeder::class,
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
        ]);
    }
}
