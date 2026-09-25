<?php

namespace App\Jobs\Member;

use App\Models\Member;
use App\Models\Membership;
use App\Models\SpiritualYear;

trait SyncsMemberRelations
{
    /**
     * @param  array<int, array<string, mixed>>  $memberships
     */
    protected function createMemberships(Member $member, array $memberships): void
    {
        foreach ($memberships as $membership) {
            Membership::create([
                'member_id' => $member->id,
                'spiritual_year_id' => SpiritualYear::query()
                    ->where('ulid', $membership['spiritual_year_ulid'])
                    ->firstOrFail()
                    ->id,
                'type' => $membership['type'],
                'approved' => $membership['approved'] ?? false,
                'amount' => $membership['amount'] ?? null,
            ]);
        }
    }
}
