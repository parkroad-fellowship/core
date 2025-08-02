<?php

namespace App\Console\Commands\Mission;

use App\Jobs\Mission\GenerateExecutiveSummaryJob;
use App\Models\Mission;
use Illuminate\Console\Command;

class RegenerateExecutiveSummaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:regenerate-executive-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate executive summaries for all missions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Regenerating executive summaries for all missions...');

        Mission::query()
            ->chunk(10, function ($missions) {
                foreach ($missions as $mission) {
                    GenerateExecutiveSummaryJob::dispatch($mission)
                        ->delay(now()->addSeconds(10));
                }
            });

        $this->info('Executive summaries regenerated successfully.');
    }
}
