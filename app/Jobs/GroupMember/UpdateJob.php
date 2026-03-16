<?php

namespace App\Jobs\GroupMember;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Member;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): void
    {
        $update = $this->data;

        if (isset($update['group_ulid'])) {
            $group = Group::query()->where('ulid', $update['group_ulid'])->firstOrFail();
            $update['group_id'] = $group->id;
            unset($update['group_ulid']);
        }

        if (isset($update['member_ulid'])) {
            $member = Member::query()->where('ulid', $update['member_ulid'])->firstOrFail();
            $update['member_id'] = $member->id;
            unset($update['member_ulid']);
        }

        GroupMember::query()
            ->where('ulid', $this->ulid)
            ->firstOrFail()
            ->update($update);
    }
}
