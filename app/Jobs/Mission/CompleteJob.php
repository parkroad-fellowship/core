<?php

namespace App\Jobs\Mission;

use App\Enums\PRFMissionStatus;
use App\Exceptions\InvalidStateTransition;
use App\Models\Mission;
use App\Models\User;
use App\Services\MissionCompletionService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Marks a mission as serviced once every required checklist item is done.
 */
class CompleteJob
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

    public function handle(MissionCompletionService $completion): Mission
    {
        return DB::transaction(function () use ($completion): Mission {
            throw_unless(
                $this->mission->status->canMoveTo(PRFMissionStatus::SERVICED),
                InvalidStateTransition::class,
                'This mission is ' . strtolower($this->mission->status->getLabel()) . ', so it cannot be completed.',
            );

            $missing = $completion->missingRequiredItems($this->mission);

            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'checklist' => ['Please complete: ' . implode(', ', $missing)],
                ]);
            }

            $completion->completeMission($this->mission);

            return $this->mission;
        });
    }
}
