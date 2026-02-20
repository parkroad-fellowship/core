<?php

namespace App\Policies;

use App\Models\PrayerResponse;
use App\Models\User;

class PrayerResponsePolicy
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
        return true;
    }

    public function view(User $user, PrayerResponse $prayerResponse): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PrayerResponse $prayerResponse): bool
    {
        return true;
    }

    public function delete(User $user, PrayerResponse $prayerResponse): bool
    {
        return true;
    }
}
