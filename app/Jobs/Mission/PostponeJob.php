<?php

namespace App\Jobs\Mission;

use App\Enums\PRFMissionStatus;
use App\Exceptions\InvalidStateTransition;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Postpones a mission, optionally moving it to new dates in the same write so MissionObserver
 * can announce MissionPostponed with the original dates.
 */
class PostponeJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data  `reason` is required; `start_date`, `end_date`, `start_time`, `end_time` are optional
     */
    public function __construct(
        public Mission $mission,
        public User $actor,
        public array $data = [],
    ) {}

    public function handle(): Mission
    {
        $reason = is_string($this->data['reason'] ?? null) ? trim($this->data['reason']) : '';

        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Give a reason for this change.']);
        }

        return DB::transaction(function () use ($reason): Mission {
            throw_unless(
                $this->mission->status->canMoveTo(PRFMissionStatus::POSTPONED),
                InvalidStateTransition::class,
                'This mission is ' . strtolower($this->mission->status->getLabel()) . ', so it cannot be postponed.',
            );

            $newSchedule = array_filter(
                array_intersect_key($this->data, array_flip(['start_date', 'end_date', 'start_time', 'end_time'])),
                fn(mixed $value): bool => filled($value),
            );

            $this->mission->fill([
                ...$newSchedule,
                'status_reason' => $reason,
            ]);

            $this->mission->status->moveTo(PRFMissionStatus::POSTPONED);

            return $this->mission;
        });
    }
}
