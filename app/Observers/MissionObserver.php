<?php

namespace App\Observers;

use App\Enums\PRFMissionStatus;
use App\Events\Mission\MissionApproved;
use App\Events\Mission\MissionCancelled;
use App\Events\Mission\MissionPostponed;
use App\Events\Mission\MissionServiced;
use App\Events\Mission\MissionWhatsAppGroupLinked;
use App\Exceptions\InvalidStateTransition;
use App\Models\Mission;
use App\States\Mission\MissionState;
use Illuminate\Support\Carbon;

/**
 * Missions change status from many screens (API, admin actions, bulk actions, edit forms),
 * so this observer is the one place that turns a status change into its domain event.
 * All side effects live in App\Listeners\Mission.
 */
class MissionObserver
{
    /**
     * The last line of defence: a status written directly (not through an action job) must still
     * be a move MissionState allows.
     */
    public function updating(Mission $mission): void
    {
        if (!$mission->isDirty('status')) {
            return;
        }

        $from = MissionState::resolveStateClass($mission->getRawOriginal('status'));
        $to = MissionState::resolveStateClass($mission->getAttributes()['status'] ?? null);

        if ($from === null || $to === null || $from === $to) {
            return;
        }

        if (!MissionState::config()->isTransitionAllowed($from::getMorphClass(), $to::getMorphClass())) {
            throw new InvalidStateTransition(sprintf(
                'A mission can’t go from %s to %s.',
                strtolower(PRFMissionStatus::from((int) $from::getMorphClass())->getLabel()),
                strtolower(PRFMissionStatus::from((int) $to::getMorphClass())->getLabel()),
            ));
        }
    }

    public function updated(Mission $mission): void
    {
        if ($mission->wasChanged('status')) {
            match ($mission->status->enum()) {
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
