<?php

namespace App\Policies;

use App\Models\PrayerPrompt;
use App\Models\User;

class PrayerPromptPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can(PrayerPrompt::permission('viewAny'));
    }

    public function view(User $user, PrayerPrompt $prayerPrompt): bool
    {
        return $user->can(PrayerPrompt::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(PrayerPrompt::permission('create'));
    }

    public function update(User $user, PrayerPrompt $prayerPrompt): bool
    {
        return $user->can(PrayerPrompt::permission('edit'));
    }

    public function delete(User $user, PrayerPrompt $prayerPrompt): bool
    {
        return $user->can(PrayerPrompt::permission('delete'));
    }
}
