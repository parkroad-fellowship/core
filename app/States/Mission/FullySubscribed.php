<?php

namespace App\States\Mission;

use App\Enums\PRFMissionStatus;

class FullySubscribed extends MissionState
{
    public static $name = '6';

    public function enum(): PRFMissionStatus
    {
        return PRFMissionStatus::FULLY_SUBSCRIBED;
    }
}
