<?php

namespace App\Observers;

use App\Enums\PRFMissionStatus;
use App\Events\Mission\MissionApproved;
use App\Events\Mission\MissionCancelled;
use App\Events\Mission\MissionPostponed;
use App\Events\Mission\MissionServiced;
use App\Events\Mission\MissionWhatsAppGroupLinked;
use App\Models\Mission;
use Illuminate\Support\Carbon;

/**
 * Missions change status from many screens (API, admin actions, bulk actions, edit forms),
 * so this observer is the one place that turns a status change into its domain event.
 * All side effects live in App\Listeners\Mission.
 */
class MissionObserver
{
    public function updated(Mission $mission): void
    {
        if ($mission->wasChanged('status')) {
            match ($mission->status) {
                PRFMissionStatus::APPROVED => MissionApproved::dispatch($mission),
                PRFMissionStatus::SERVICED => MissionServiced::dispatch($mission),
                PRFMissionStatus::POSTPONED => MissionPostponed::dispatch(
                    $mission,
                    $this->originalDate($mission, 'start_date'),
                    $this->originalDate($mission, 'end_date'),
                ),
                PRFMissionStatus::CANCELLED => MissionCancelled::dispatch($mission),
                default => null,
            };
        }

        if ($mission->wasChanged('whats_app_link') && filled($mission->whats_app_link)) {
            MissionWhatsAppGroupLinked::dispatch($mission);
        }
    }

    private function originalDate(Mission $mission, string $attribute): ?Carbon
    {
        $original = $mission->getOriginal($attribute);

        return $original === null ? null : Carbon::parse($original);
    }
}
