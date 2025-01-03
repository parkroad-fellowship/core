<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Gift;
use App\Models\Member;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = Member::factory()
            ->count(3)
            ->create();

        $members->each(function ($member) {
            // Attach departments
            $member->departments()->attach(
                Department::inRandomOrder()->limit(rand(1, 3))->get()
            );

            // Attach gifts
            $member->gifts()->attach(
                Gift::inRandomOrder()->limit(rand(1, 3))->get()
            );
        });

    }
}
