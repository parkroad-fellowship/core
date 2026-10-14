<?php

namespace App\Jobs\GroupMember;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Member;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;
    use ResolvesULIDs;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): GroupMember
    {
        $groupMember = GroupMember::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'group_ulid' => Group::class,
            'member_ulid' => Member::class,
        ]);

        $groupMember->update($attributes);

        return $groupMember;
    }
}
