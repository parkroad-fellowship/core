<?php

namespace App\Policies;

use App\Models\StudentEnquiryReply;
use App\Models\User;

class StudentEnquiryReplyPolicy
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
        return $user->can(StudentEnquiryReply::permission('viewAny'));
    }

    public function view(User $user, StudentEnquiryReply $studentEnquiryReply): bool
    {
        return $user->can(StudentEnquiryReply::permission('view'));
    }

    public function create(User $user): bool
    {
        return $user->can(StudentEnquiryReply::permission('create'));
    }

    public function update(User $user, StudentEnquiryReply $studentEnquiryReply): bool
    {
        return $user->can(StudentEnquiryReply::permission('edit'));
    }

    public function delete(User $user, StudentEnquiryReply $studentEnquiryReply): bool
    {
        return $user->can(StudentEnquiryReply::permission('delete'));
    }
}
