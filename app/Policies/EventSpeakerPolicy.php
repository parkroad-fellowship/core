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
        return $user->can(EventSpeaker::permission('viewAny'));
    }

    public function view(User $user, EventSpeaker $eventSpeaker): bool
    {
        return $user->can(EventSpeaker::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(EventSpeaker::permission('create'));
    }

    public function update(User $user, EventSpeaker $eventSpeaker): bool
    {
        return $user->can(EventSpeaker::permission('edit'));
    }

    public function delete(User $user, EventSpeaker $eventSpeaker): bool
    {
        return $user->can(EventSpeaker::permission('delete'));
    }
}
