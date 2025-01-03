<?php

namespace App\Observers;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Str;

class MemberObserver
{
    /**
     * Handle the Member "created" event.
     */
    public function created(Member $member): void
    {
        // Create a corresponding user
        $user = User::updateOrCreate([
            'email' => $member->email,
        ], [
            'name' => $member->full_name,
            'email' => $member->email,
            'password' => bcrypt(Str::random(16)),
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
