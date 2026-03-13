<?php

namespace App\Policies;

use App\Models\LessonModule;
use App\Models\User;

class LessonModulePolicy
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
        return $user->can(LessonModule::permission('viewAny'));
    }

    public function view(User $user, LessonModule $lessonModule): bool
    {
        return $user->can(LessonModule::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(LessonModule::permission('create'));
    }

    public function update(User $user, LessonModule $lessonModule): bool
    {
        return $user->can(LessonModule::permission('edit'));
    }

    public function delete(User $user, LessonModule $lessonModule): bool
    {
        return $user->can(LessonModule::permission('delete'));
    }
}
