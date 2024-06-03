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
            UserSeeder::class,
        ]);

        $this->call([
            ChurchSeeder::class,
            MaritalStatusSeeder::class,
            ProfessionSeeder::class,
            GiftSeeder::class,
            DepartmentSeeder::class,
        ]);

        $this->call([
            MemberSeeder::class,
        ]);

        $this->call([
            ContactTypeSeeder::class,
            SchoolSeeder::class,
        ]);

        $this->call([
            SchoolTermSeeder::class,
        ]);
    }
}
