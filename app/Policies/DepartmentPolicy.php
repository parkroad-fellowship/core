<?php

namespace App\Policies;

use App\Models\Department;

class DepartmentPolicy extends BasePolicy
{
    protected string $modelClass = Department::class;
}
