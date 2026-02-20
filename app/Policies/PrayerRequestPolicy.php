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
        return true;
    }

    public function view(User $user, PrayerRequest $prayerRequest): bool
    {
        return $user->member?->id === $prayerRequest->member_id
            || $user->hasRole('prayer secretary');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PrayerRequest $prayerRequest): bool
    {
        return $user->member?->id === $prayerRequest->member_id
            || $user->hasRole('prayer secretary');
    }

    public function delete(User $user, PrayerRequest $prayerRequest): bool
    {
        return $user->member?->id === $prayerRequest->member_id
            || $user->hasRole('prayer secretary');
    }
}
