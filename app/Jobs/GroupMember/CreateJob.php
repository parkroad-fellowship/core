<?php

namespace App\Jobs\GroupMember;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Member;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): GroupMember
    {
        $group = Group::query()->where('ulid', $this->data['group_ulid'])->firstOrFail();
        $member = Member::query()->where('ulid', $this->data['member_ulid'])->firstOrFail();

        return GroupMember::create([
            'group_id' => $group->id,
            'member_id' => $member->id,
            'start_date' => $this->data['start_date'],
            'end_date' => $this->data['end_date'] ?? null,
        ]);
    }
}
