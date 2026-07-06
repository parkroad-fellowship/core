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
        // Create the full_name if it's missing
        // Done this way to avoid race conditions
        if (! $member->full_name) {
            $member->updateQuietly([
                'full_name' => $member->first_name.' '.$member->last_name,
            ]);
            $member->refresh();
        }

        if ($member->user_id) {
            return;
        }

        $email = $member->email ?? $member->personal_email;
        if (! $email) {
            return;
        }

        // Create a corresponding user
        $user = User::updateOrCreate([
            'email' => $email,
        ], [
            'name' => $member->full_name,
            'email' => $email,
            'password' => Utils::randomPassword(),
        ]);

        $user->assignRole([
            'member',
        ]);

        // Link the new user account to this member record
        $member->updateQuietly([
            'user_id' => $user->id,
            'email' => $email,
        ]);

        $allGroup = Group::firstOrCreate(
            ['name' => config('prf.app.global_group', 'All')],
            [
                'description' => 'All members group',
                'official_whatsapp_link' => '',
                'is_active' => 2,
            ],
        );
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
        // Create the full_name if it's missing
        // Done this way to avoid race conditions
        if ($member->wasChanged(['first_name', 'last_name'])) {
            $member->updateQuietly([
                'full_name' => $member->first_name.' '.$member->last_name,
            ]);
            $member->refresh();

            User::query()
                ->where('id', $member->user_id)
                ->update([
                    'name' => $member->full_name,
                ]);
        }
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
