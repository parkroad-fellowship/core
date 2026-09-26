<?php

namespace App\States\Mission;

use App\Enums\PRFMissionStatus;

class Cancelled extends MissionState
{
    public static $name = '4';

    public function enum(): PRFMissionStatus
    {
        return PRFMissionStatus::CANCELLED;
    }
}
