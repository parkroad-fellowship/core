<?php

namespace App\States\Mission;

use App\Enums\PRFMissionStatus;

class Serviced extends MissionState
{
    public static $name = '5';

    public function enum(): PRFMissionStatus
    {
        return PRFMissionStatus::SERVICED;
    }
}
