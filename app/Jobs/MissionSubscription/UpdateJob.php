<?php

namespace App\Jobs\MissionSubscription;

use App\Models\MissionSubscription;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): MissionSubscription
    {
        $missionSubscription = MissionSubscription::query()->where('ulid', $this->ulid)->firstOrFail();

        // Only the fields that were sent: the app changes the status, the panel may also set the role.
        $attributes = array_intersect_key($this->data, array_flip(['status', 'mission_role']));

        $missionSubscription->update($attributes);

        return $missionSubscription;
    }
}
