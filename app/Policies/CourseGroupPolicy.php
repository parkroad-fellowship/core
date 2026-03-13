<?php

namespace App\Policies;

use App\Models\CourseGroup;
use App\Models\User;

class CourseGroupPolicy
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
        return $user->can(CourseGroup::permission('viewAny'));
    }

    public function view(User $user, CourseGroup $courseGroup): bool
    {
        return $user->can(CourseGroup::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(CourseGroup::permission('create'));
    }

    public function update(User $user, CourseGroup $courseGroup): bool
    {
        return $user->can(CourseGroup::permission('edit'));
    }

    public function delete(User $user, CourseGroup $courseGroup): bool
    {
        return $user->can(CourseGroup::permission('delete'));
    }
}
