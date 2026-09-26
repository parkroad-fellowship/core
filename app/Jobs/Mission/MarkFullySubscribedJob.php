<?php

namespace App\Jobs\Mission;

use App\Enums\PRFMissionStatus;
use App\Exceptions\InvalidStateTransition;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

/**
 * Marks an approved mission as fully subscribed, so it stops asking for volunteers.
 */
class MarkFullySubscribedJob
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
                $this->mission->status->canMoveTo(PRFMissionStatus::FULLY_SUBSCRIBED),
                InvalidStateTransition::class,
                'This mission is '
                . strtolower($this->mission->status->getLabel())
                . ', so it cannot be marked fully subscribed.',
            );

            $this->mission->status->moveTo(PRFMissionStatus::FULLY_SUBSCRIBED);

            return $this->mission;
        });
    }
}
