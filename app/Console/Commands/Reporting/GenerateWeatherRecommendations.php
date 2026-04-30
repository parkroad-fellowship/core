<?php

namespace App\Console\Commands\Reporting;

use App\Jobs\Mission\GenerateWeatherRecommendationsJob;
use App\Models\Mission;
use App\Models\PRFEvent;
use Illuminate\Console\Command;

class GenerateWeatherRecommendations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-weather-recommendations';

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

        Mission::chunkById(10, function ($missions) use (&$delayInSeconds) {
            foreach ($missions as $mission) {
                GenerateWeatherRecommendationsJob::dispatch($mission)->delay(now()->addSeconds($delayInSeconds));

                $delayInSeconds += 62; // Increase delay for next job
            }
        });

        PRFEvent::chunkById(20, function ($prfEvents) use (&$delayInSeconds) {
            foreach ($prfEvents as $prfEvent) {
                \App\Jobs\PRFEvent\GenerateWeatherRecommendationsJob::dispatch($prfEvent)->delay(now()->addSeconds($delayInSeconds));

                $delayInSeconds += 62; // Increase delay for next job
            }
        });
    }
}
