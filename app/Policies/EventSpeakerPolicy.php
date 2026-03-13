<?php

namespace App\Policies;

use App\Models\EventSpeaker;
use App\Models\User;

class EventSpeakerPolicy
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

    public function view(User $user, EventSpeaker $eventSpeaker): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, EventSpeaker $eventSpeaker): bool
    {
        return true;
    }

    public function delete(User $user, EventSpeaker $eventSpeaker): bool
    {
        return true;
    }
}
