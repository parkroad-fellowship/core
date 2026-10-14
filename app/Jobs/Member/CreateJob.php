<?php

namespace App\Jobs\Member;

use App\Jobs\Concerns\ResolvesULIDs;
use App\Models\Church;
use App\Models\Department;
use App\Models\Gift;
use App\Models\MaritalStatus;
use App\Models\Member;
use App\Models\Profession;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class CreateJob
{
    use Dispatchable;
    use ResolvesULIDs;
    use SyncsMemberRelations;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
    ) {}

    public function handle(): Member
    {
        return DB::transaction(function (): Member {
            $attributes = $this->resolveULIDs($this->data, [
                'church_ulid' => Church::class,
                'profession_ulid' => Profession::class,
                'marital_status_ulid' => MaritalStatus::class,
            ]);

            $departmentUlids = $attributes['department_ulids'] ?? [];
            $giftUlids = $attributes['gift_ulids'] ?? [];
            $memberships = $attributes['memberships'] ?? [];
            unset($attributes['department_ulids'], $attributes['gift_ulids'], $attributes['memberships']);

            $member = Member::create($attributes);

            if ($departmentUlids !== []) {
                $member->departments()->sync(Department::query()->whereIn('ulid', $departmentUlids)->pluck('id'));
            }

            if ($giftUlids !== []) {
                $member->gifts()->sync(Gift::query()->whereIn('ulid', $giftUlids)->pluck('id'));
            }

            $this->createMemberships($member, $memberships);

            OnboardJob::dispatchSync($member);

            return $member;
        });
    }
}
