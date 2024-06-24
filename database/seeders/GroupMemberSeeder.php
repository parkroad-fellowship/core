<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Member;
use Illuminate\Database\Seeder;

class GroupMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Group::cursor() as $group) {
            // Attach courses to a group
            $random = rand(3, 6);
            $group->groupMembers()->createMany(
                Member::inRandomOrder()->limit($random)->get()->map(function ($member) use ($random) {
                    return [
                        'member_id' => $member->id,
                        'start_date' => now()->addDays($random),
                        'end_date' => now()->addYears($random),
                    ];
                })->toArray()
            );
        }
    }
}
