<?php

namespace App\Console\Commands\School;

use App\Jobs\School\CalculateRouteJob;
use App\Models\School;
use Illuminate\Console\Command;

class ReCalculateDistancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:re-calculate-distances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate distances for all schools in the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Recalculating distances for all schools in the database...');

        School::query()
            ->chunk(30, function ($schools) {
                foreach ($schools as $school) {
                    CalculateRouteJob::dispatch($school);
                }
            });

        $this->info('Done!');
    }
}
