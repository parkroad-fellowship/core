<?php

namespace App\Jobs\Member;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\Church;
use App\Models\Department;
use App\Models\Gift;
use App\Models\MaritalStatus;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Profession;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class UpdateJob
{
    use Dispatchable;
    use ResolvesULIDs;
    use SyncsMemberRelations;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): Member
    {
        return DB::transaction(function (): Member {
            $member = Member::query()->where('ulid', $this->ulid)->firstOrFail();

            $attributes = $this->resolveULIDs($this->data, [
                'church_ulid' => Church::class,
                'profession_ulid' => Profession::class,
                'marital_status_ulid' => MaritalStatus::class,
            ]);

            $departmentUlids = $attributes['department_ulids'] ?? null;
            $giftUlids = $attributes['gift_ulids'] ?? null;
            $memberships = $attributes['memberships'] ?? null;
            unset($attributes['department_ulids'], $attributes['gift_ulids'], $attributes['memberships']);

            $member->update($attributes);

            if (is_array($departmentUlids)) {
                $member->departments()->sync(Department::query()->whereIn('ulid', $departmentUlids)->pluck('id'));
            }

            if (is_array($giftUlids)) {
                $member->gifts()->sync(Gift::query()->whereIn('ulid', $giftUlids)->pluck('id'));
            }

            if (is_array($memberships)) {
                $member
                    ->memberships()
                    ->get()
                    ->each(fn(Membership $membership) => $membership->delete());
                $this->createMemberships($member, $memberships);
            }

            return $member;
        });
    }
}
