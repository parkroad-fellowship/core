<?php

namespace App\Observers;

use App\Models\Member;
use App\Models\User;

class MemberObserver
{
    /**
     * Handle the Member "created" event.
     */
    public function created(Member $member): void
    {
        // Create a corresponding user
        $user = User::create([
            'name' => $member->full_name,
            'email' => $member->email,
            'password' => bcrypt('password'),
        ]);

        // Link the new user account to this member record
        $member->update([
            'user_id' => $user->id,
        ]);
    }

    /**
     * Handle the Member "updated" event.
     */
    public function updated(Member $member): void
    {
        //
    }

    /**
     * Handle the Member "deleted" event.
     */
    public function deleted(Member $member): void
    {
        //
    }

    /**
     * Handle the Member "restored" event.
     */
    public function restored(Member $member): void
    {
        //
    }

    /**
     * Handle the Member "force deleted" event.
     */
    public function forceDeleted(Member $member): void
    {
        //
    }
}
