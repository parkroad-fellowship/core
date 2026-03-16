<?php

namespace App\Policies;

use App\Models\CourseModule;
use App\Models\User;

class CourseModulePolicy
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
        return $user->can(CourseModule::permission('viewAny'));
    }

    public function view(User $user, CourseModule $courseModule): bool
    {
        return $user->can(CourseModule::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(CourseModule::permission('create'));
    }

    public function update(User $user, CourseModule $courseModule): bool
    {
        return $user->can(CourseModule::permission('edit'));
    }

    public function delete(User $user, CourseModule $courseModule): bool
    {
        return $user->can(CourseModule::permission('delete'));
    }
}
