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

        $attributes = ['status' => $this->data['status']];

        $missionSubscription->update($attributes);

        return $missionSubscription;
    }
}
