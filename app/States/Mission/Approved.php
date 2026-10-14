<?php

namespace App\States\Mission;

use App\Enums\PRFMissionStatus;

class Approved extends MissionState
{
    public static $name = '2';

    public function enum(): PRFMissionStatus
    {
        return PRFMissionStatus::APPROVED;
    }
}
