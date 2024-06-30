<?php

namespace Database\Seeders;

use App\Models\StudentEnquiryReply;
use Illuminate\Database\Seeder;

class StudentEnquiryReplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StudentEnquiryReply::factory()
            ->count(6)
            ->create();
    }
}
