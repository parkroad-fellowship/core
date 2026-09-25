<?php

namespace App\Observers;

use App\Actions\Tenant\AddTenantMemberAction;
use App\Actions\Tenant\RemoveTenantMemberAction;
use App\Enums\PRFMemberEmailMode;
use App\Events\Member\MemberDeleted;
use App\Events\Member\MemberRestored;
use App\Helpers\Utils;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;

/**
 * Keeps a member consistent with its own login User. Identity provisioning (creating the
 * User, roles, groups, Workspace mailbox) lives in App\Jobs\Member\OnboardJob.
 *
 * A User can belong to several tenants (personal-email mode), so tenant-specific changes
 * only touch the shared User when this tenant is its only one.
 */
class MemberObserver
{
    public function created(Member $member): void
    {
        $this->syncFullName($member);
    }

    public function updated(Member $member): void
    {
        if ($member->wasChanged(['first_name', 'last_name'])) {
            $this->syncFullName($member);

            $user = $member->user;

            if ($user !== null && $this->belongsOnlyToThisTenant($user, $member)) {
                $user->update(['name' => $member->full_name]);
            }
        }

        if ($member->wasChanged('personal_email') && Utils::memberEmailMode() === PRFMemberEmailMode::PERSONAL) {
            $this->syncPersonalLoginEmail($member);
        }
    }

    /**
     * Deleting a member removes them from this tenant only. Their User is deleted when it
     * belongs to no other tenant.
     */
    public function deleted(Member $member): void
    {
        $user = User::withTrashed()->find($member->user_id);
        $tenant = Tenant::query()->find($member->tenant_id);

        if ($user !== null) {
            if ($tenant !== null) {
                app(RemoveTenantMemberAction::class)->handle($tenant, $user);
            }

            if (!$user->tenants()->exists()) {
                $member->isForceDeleting() ? $user->forceDelete() : $user->delete();
            }
        }

        MemberDeleted::dispatch($member);
    }

    public function restored(Member $member): void
    {
        $user = User::withTrashed()->find($member->user_id);
        $tenant = Tenant::query()->find($member->tenant_id);

        if ($user !== null) {
            if ($user->trashed()) {
                $user->restore();
            }

            if ($tenant !== null) {
                app(AddTenantMemberAction::class)->handle($tenant, $user, 'member');
            }
        }

        MemberRestored::dispatch($member);
    }

    private function syncFullName(Member $member): void
    {
        $fullName = trim("{$member->first_name} {$member->last_name}");

        if ($fullName !== '' && $member->full_name !== $fullName) {
            // Writing back to the model being saved; skip events to avoid re-entering this observer.
            $member->updateQuietly(['full_name' => $fullName]);
        }
    }

    /**
     * In personal-email mode the personal email is the login; keep the User in step when the
     * address is not already someone else's (request validation reports that case).
     */
    private function syncPersonalLoginEmail(Member $member): void
    {
        $user = $member->user;
        $email = strtolower(trim((string) $member->personal_email));

        if ($user === null || $email === '' || !$this->belongsOnlyToThisTenant($user, $member)) {
            return;
        }

        if (User::withTrashed()->where('email', $email)->whereKeyNot($user->getKey())->exists()) {
            return;
        }

        $user->update(['email' => $email]);
        $member->updateQuietly(['email' => $email]);
    }

    private function belongsOnlyToThisTenant(User $user, Member $member): bool
    {
        return !$user->tenants()->where('tenants.id', '!=', $member->tenant_id)->exists();
    }
}
