<?php

namespace App\States\Mission;

use App\Enums\PRFMissionStatus;

class Rejected extends MissionState
{
    public static $name = '3';

    public function enum(): PRFMissionStatus
    {
        return PRFMissionStatus::REJECTED;
    }
}
