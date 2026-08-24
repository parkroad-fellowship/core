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
        $members = Member::factory()->count(3)->create();

        // Raw pivot attaches bypass BelongsToTenant stamping, so the current
        // tenant must be set explicitly on each pivot row.
        $tenantId = tenant('id');

        $members->each(function ($member) use ($tenantId) {
            // Attach departments
            $member->departments()->attach(Department::inRandomOrder()
                ->limit(rand(1, 3))
                ->get()
                ->mapWithKeys(fn($department) => [$department->getKey() => ['tenant_id' => $tenantId]]));

            // Attach gifts
            $member->gifts()->attach(Gift::inRandomOrder()
                ->limit(rand(1, 3))
                ->get()
                ->mapWithKeys(fn($gift) => [$gift->getKey() => ['tenant_id' => $tenantId]]));
        });
    }
}
