<?php

namespace App\Jobs\MissionExpense;

use App\Models\MissionExpense;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $ulid,
        public array $data,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $data = $this->data;

        MissionExpense::query()
            ->where('ulid', $this->ulid)
            ->update($data);
    }
}
