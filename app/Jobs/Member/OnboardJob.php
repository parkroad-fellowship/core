<?php

namespace App\Jobs\Member;

use App\Actions\Tenant\AddTenantMemberAction;
use App\Enums\PRFRole;
use App\Events\Member\MemberOnboarded;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Member;
use App\Services\Members\MemberIdentityService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

/**
 * Gives a newly created member a login user, the member role, tenant membership and the
 * global group. Every creation path (API, admin panel, imports, factories) calls this job.
 */
class OnboardJob
{
    use Dispatchable;

    public function __construct(
        public Member $member,
    ) {}

    public function handle(MemberIdentityService $identity, AddTenantMemberAction $addTenantMember): Member
    {
        return DB::transaction(function () use ($identity, $addTenantMember): Member {
            $user = $identity->provisionUser($this->member);

            if (!$user->hasRole(PRFRole::MEMBER)) {
                $user->assignRole(PRFRole::MEMBER);
            }

            if (tenancy()->initialized) {
                $addTenantMember->handle(tenancy()->tenant, $user, 'member');
            }

            $globalGroup = Group::query()->where('name', config('prf.app.global_group'))->first();

            if ($globalGroup !== null) {
                GroupMember::query()->firstOrCreate([
                    'group_id' => $globalGroup->id,
                    'member_id' => $this->member->id,
                ], ['start_date' => now()]);
            }

            MemberOnboarded::dispatch($this->member);

            return $this->member;
        });
    }
}
