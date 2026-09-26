<?php

namespace App\Jobs\Mission;

use App\Enums\PRFMissionStatus;
use App\Exceptions\InvalidStateTransition;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data  `reason` is required
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
                $this->mission->status->canMoveTo(PRFMissionStatus::CANCELLED),
                InvalidStateTransition::class,
                'This mission is ' . strtolower($this->mission->status->getLabel()) . ', so it cannot be cancelled.',
            );

            $this->mission->fill([
                'status_reason' => $reason,
            ]);

            $this->mission->status->moveTo(PRFMissionStatus::CANCELLED);

            return $this->mission;
        });
    }
}
