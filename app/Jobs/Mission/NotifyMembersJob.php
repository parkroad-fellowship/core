<?php

namespace App\Jobs\Mission;

use App\Models\Mission;
use App\Models\User;
use App\Notifications\Mission\NewMissionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class NotifyMembersJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Mission $mission,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mission = $this->mission;

        User::chunk(30, function ($users) use ($mission) {
            foreach ($users as $user) {
                $user->notify(new NewMissionNotification($mission));
            }
        });
    }
}
