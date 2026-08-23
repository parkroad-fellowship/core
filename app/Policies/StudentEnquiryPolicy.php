<?php

namespace App\Policies;

use App\Models\StudentEnquiry;

class StudentEnquiryPolicy extends BasePolicy
{
    protected string $modelClass = StudentEnquiry::class;
}
