<?php

namespace App\Policies;

use App\Models\PrayerRequest;
use App\Models\User;

class PrayerRequestPolicy
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
        return $user->can(PrayerRequest::permission('viewAny'));
    }

    public function view(User $user, PrayerRequest $prayerRequest): bool
    {
        return $user->can(PrayerRequest::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(PrayerRequest::permission('create'));
    }

    public function update(User $user, PrayerRequest $prayerRequest): bool
    {
        return $user->can(PrayerRequest::permission('edit'));
    }

    public function delete(User $user, PrayerRequest $prayerRequest): bool
    {
        return $user->can(PrayerRequest::permission('delete'));
    }
}
