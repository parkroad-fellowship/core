<?php

namespace App\Policies;

use App\Models\StudentEnquiryReply;

class StudentEnquiryReplyPolicy extends BasePolicy
{
    protected string $modelClass = StudentEnquiryReply::class;
}
