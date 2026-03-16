<?php

namespace App\Policies;

use App\Models\StudentEnquiry;
use App\Models\User;

class StudentEnquiryPolicy
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
        return $user->can(StudentEnquiry::permission('viewAny'));
    }

    public function view(User $user, StudentEnquiry $studentEnquiry): bool
    {
        return $user->can(StudentEnquiry::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(StudentEnquiry::permission('create'));
    }

    public function update(User $user, StudentEnquiry $studentEnquiry): bool
    {
        return $user->can(StudentEnquiry::permission('edit'));
    }

    public function delete(User $user, StudentEnquiry $studentEnquiry): bool
    {
        return $user->can(StudentEnquiry::permission('delete'));
    }
}
