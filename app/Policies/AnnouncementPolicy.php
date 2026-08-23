<?php

namespace App\Policies;

use App\Models\Announcement;

class AnnouncementPolicy extends BasePolicy
{
    protected string $modelClass = Announcement::class;
}
