<?php

namespace Database\Seeders;

use App\Models\StudentEnquiry;
use Illuminate\Database\Seeder;

class StudentEnquirySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StudentEnquiry::factory()
            ->count(3)
            ->create();
    }
}
