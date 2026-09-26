<?php

namespace App\States\Mission;

use App\Enums\PRFMissionStatus;
use Spatie\ModelStates\StateCaster;

/**
 * Also accepts a PRFMissionStatus case when setting the status (factories, older code), storing
 * the same integer as its state class.
 */
class MissionStateCaster extends StateCaster
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value instanceof PRFMissionStatus) {
            $value = MissionState::classFor($value);
        }

        return parent::set($model, $key, $value, $attributes);
    }
}
