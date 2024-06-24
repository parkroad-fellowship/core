<?php

namespace App\Jobs\MissionSubscription;

use App\Models\MissionSubscription;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
        public string $missionSubscriptionUlid,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->data;
        $missionSubscriptionUlid = $this->missionSubscriptionUlid;

        MissionSubscription::query()
            ->where('ulid', $missionSubscriptionUlid)
            ->update([
                'status' => $data['status'],
            ]);
    }
}
