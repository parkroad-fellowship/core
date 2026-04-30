<?php

namespace App\Console\Commands\Reporting;

use App\Enums\PRFMissionStatus;
use App\Jobs\Mission\GenerateExecutiveSummaryJob;
use App\Models\Mission;
use Illuminate\Console\Command;

class GenerateExecutiveSummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-executive-summaries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $delayInSeconds = 0;

        Mission::query()
            ->whereIn('status', [
                PRFMissionStatus::SERVICED->value,
                PRFMissionStatus::CANCELLED->value,
                PRFMissionStatus::POSTPONED->value,
            ])
            ->orderBy('start_date')
            ->chunkById(10, function ($missions) use (&$delayInSeconds) {
                foreach ($missions as $mission) {
                    GenerateExecutiveSummaryJob::dispatch($mission)->delay(now()->addSeconds($delayInSeconds));

                    $delayInSeconds += 0; // Increase delay for next job
                }
            });
    }
}
