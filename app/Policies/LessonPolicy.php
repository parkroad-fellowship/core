<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
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
        return $user->can(Lesson::permission('viewAny'));
    }

    public function view(User $user, Lesson $lesson): bool
    {
        return $user->can(Lesson::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Lesson::permission('create'));
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->can(Lesson::permission('edit'));
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->can(Lesson::permission('delete'));
    }
}
