<?php

namespace App\States\Mission;

use App\Enums\PRFMissionStatus;

class Postponed extends MissionState
{
    public static $name = '7';

    public function enum(): PRFMissionStatus
    {
        return PRFMissionStatus::POSTPONED;
    }
}
