<?php

namespace App\Policies;

use App\Models\AppSetting;

class AppSettingPolicy extends BasePolicy
{
    protected string $modelClass = AppSetting::class;
}
