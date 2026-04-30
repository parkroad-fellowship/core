<?php

namespace App\Policies;

use App\Models\LessonMember;
use App\Models\User;

class LessonMemberPolicy
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
        return $user->can(LessonMember::permission('viewAny'));
    }

    public function view(User $user, LessonMember $lessonMember): bool
    {
        return $user->can(LessonMember::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(LessonMember::permission('create'));
    }

    public function update(User $user, LessonMember $lessonMember): bool
    {
        return $user->can(LessonMember::permission('edit'));
    }

    public function delete(User $user, LessonMember $lessonMember): bool
    {
        return $user->can(LessonMember::permission('delete'));
    }
}
