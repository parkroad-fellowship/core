<?php

namespace App\Observers;

use App\Models\AppSetting;

class AppSettingObserver
{
    public function created(AppSetting $appSetting): void
    {
        AppSetting::clearCache();
    }

    public function updated(AppSetting $appSetting): void
    {
        AppSetting::clearCache();
    }

    public function deleted(AppSetting $appSetting): void
    {
        AppSetting::clearCache();
    }
}
