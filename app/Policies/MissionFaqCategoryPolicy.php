<?php

namespace App\Policies;

use App\Models\MissionFaqCategory;

class MissionFaqCategoryPolicy extends BasePolicy
{
    protected string $modelClass = MissionFaqCategory::class;
}
