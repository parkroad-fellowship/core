<?php

namespace App\States\Mission;

use App\Enums\PRFMissionStatus;
use App\Exceptions\InvalidStateTransition;
use App\Models\Mission;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Where a mission is in its life, and the only moves allowed from there. The values stored are
 * the same integers as PRFMissionStatus (1–7), so the database, queries and the apps are unchanged.
 *
 * Move a mission with its action jobs (ApproveJob, PostponeJob…), which call moveTo(). Anything
 * that writes `status` directly is checked against the same table by MissionObserver.
 *
 * @extends State<Mission>
 */
abstract class MissionState extends State
{
    abstract public function enum(): PRFMissionStatus;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Pending::class)
            ->allowTransitions([
                [Pending::class,         Approved::class],
                [Pending::class,         Rejected::class],
                [Pending::class,         Cancelled::class],

                [Approved::class,        FullySubscribed::class],
                [Approved::class,        Postponed::class],
                [Approved::class,        Cancelled::class],
                [Approved::class,        Serviced::class],

                [FullySubscribed::class, Approved::class],
                [FullySubscribed::class, Postponed::class],
                [FullySubscribed::class, Cancelled::class],
                [FullySubscribed::class, Serviced::class],

                [Postponed::class,       Approved::class],
                [Postponed::class,       Cancelled::class],
            ]);
    }

    /**
     * @param  array<mixed>  $arguments
     */
    public static function castUsing(array $arguments): MissionStateCaster
    {
        return new MissionStateCaster(static::class);
    }

    /**
     * @return class-string<MissionState>
     */
    public static function classFor(PRFMissionStatus $status): string
    {
        return match ($status) {
            PRFMissionStatus::PENDING => Pending::class,
            PRFMissionStatus::APPROVED => Approved::class,
            PRFMissionStatus::REJECTED => Rejected::class,
            PRFMissionStatus::CANCELLED => Cancelled::class,
            PRFMissionStatus::SERVICED => Serviced::class,
            PRFMissionStatus::FULLY_SUBSCRIBED => FullySubscribed::class,
            PRFMissionStatus::POSTPONED => Postponed::class,
        };
    }

    public function getLabel(): string
    {
        return $this->enum()->getLabel();
    }

    public function getColor(): string
    {
        return $this->enum()->getColor();
    }

    public function is(PRFMissionStatus ...$statuses): bool
    {
        return in_array($this->enum(), $statuses, true);
    }

    public function canMoveTo(PRFMissionStatus $to): bool
    {
        return $this->canTransitionTo(self::classFor($to));
    }

    /**
     * Moves the mission (saving any other changes made to it in the same save), or explains why
     * it can't move.
     */
    public function moveTo(PRFMissionStatus $to): Mission
    {
        try {
            $mission = $this->transitionTo(self::classFor($to));
        } catch (CouldNotPerformTransition) {
            throw new InvalidStateTransition(
                'This mission is '
                . strtolower($this->getLabel())
                . ', so it cannot be '
                . strtolower($to->getLabel())
                . '.',
            );
        }

        assert($mission instanceof Mission);

        return $mission;
    }
}
