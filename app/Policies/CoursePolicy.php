<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
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
        return $user->can(Course::permission('viewAny'));
    }

    public function view(User $user, Course $course): bool
    {
        return $user->can(Course::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(Course::permission('create'));
    }

    public function update(User $user, Course $course): bool
    {
        return $user->can(Course::permission('edit'));
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->can(Course::permission('delete'));
    }
}
