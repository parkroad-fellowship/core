<?php

namespace App\Jobs\Mission;

use App\Enums\PRFMissionStatus;
use App\Exceptions\InvalidStateTransition;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

/**
 * Approves a pending mission, or reinstates a postponed or fully subscribed one. MissionObserver
 * turns the status change into MissionApproved.
 */
class ApproveJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public Mission $mission,
        public User $actor,
        public array $data = [],
    ) {}

    public function handle(): Mission
    {
        return DB::transaction(function (): Mission {
            throw_unless(
                $this->mission->status->canMoveTo(PRFMissionStatus::APPROVED),
                InvalidStateTransition::class,
                'This mission is ' . strtolower($this->mission->status->getLabel()) . ', so it cannot be approved.',
            );

            $this->mission->fill([
                'status_reason' => null,
            ]);

            $this->mission->status->moveTo(PRFMissionStatus::APPROVED);

            return $this->mission;
        });
    }
}
