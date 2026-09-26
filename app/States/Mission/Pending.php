<?php

namespace App\States\Mission;

use App\Enums\PRFMissionStatus;

class Pending extends MissionState
{
    public static $name = '1';

    public function enum(): PRFMissionStatus
    {
        return PRFMissionStatus::PENDING;
    }
}
