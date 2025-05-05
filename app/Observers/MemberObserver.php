<?php

namespace App\Observers;

use App\Helpers\Utils;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Member;
use App\Models\User;

class MemberObserver
{
    /**
     * Handle the Member "created" event.
     */
    public function created(Member $member): void
    {
        // Create the full_name
        $member->update([
            'full_name' => $member->first_name.' '.$member->last_name,
        ]);

        if ($member->user_id) {
            return;
        }

        $prfEmail = Utils::generatePRFEmail(
            model: Member::class,
            fullName: $member->full_name,
        );

        // Create a corresponding user
        $user = User::updateOrCreate([
            'email' => $prfEmail,
        ], [
            'name' => $member->full_name,
            'email' => $prfEmail,
            'password' => Utils::randomPassword(),
        ]);

        $user->assignRole([
            'member',
        ]);

        // Link the new user account to this member record
        $member->update([
            'user_id' => $user->id,
            'email' => $prfEmail,
        ]);

        $allGroup = Group::where('name', config('prf.app.global_group'))->first();
        GroupMember::create([
            'group_id' => $allGroup->id,
            'member_id' => $member->id,
            'start_date' => now(),
        ]);
    }

    /**
     * Handle the Member "updated" event.
     */
    public function updated(Member $member): void
    {
        // Create the full_name
        $member->update([
            'full_name' => $member->first_name.' '.$member->last_name,
        ]);

        User::query()
            ->where('id', $member->user_id)
            ->update([
                'name' => $member->full_name,
            ]);
    }

    /**
     * Handle the Member "deleted" event.
     */
    public function deleted(Member $member): void
    {
        User::query()
            ->where('id', $member->user_id)
            ->delete();
    }

    /**
     * Handle the Member "restored" event.
     */
    public function restored(Member $member): void
    {
        User::withTrashed()
            ->where('id', $member->user_id)
            ->restore();
    }

    /**
     * Handle the Member "force deleted" event.
     */
    public function forceDeleted(Member $member): void
    {
        User::withTrashed()
            ->where('id', $member->user_id)
            ->forceDelete();
    }
}
