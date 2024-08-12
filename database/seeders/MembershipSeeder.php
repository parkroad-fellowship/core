<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Membership;
use Illuminate\Database\Seeder;

class MembershipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Member::query()->select('id')->get() as $member) {
            Membership::factory()
                ->count(1)
                ->create([
                    'member_id' => $member->id,
                ]);
        }
    }
}
