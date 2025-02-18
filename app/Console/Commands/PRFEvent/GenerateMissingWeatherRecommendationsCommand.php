<?php

namespace App\Console\Commands\PRFEvent;

use App\Jobs\PRFEvent\GenerateWeatherForecastJob;
use App\Jobs\PRFEvent\GenerateWeatherRecommendationsJob;
use App\Models\PRFEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class GenerateMissingWeatherRecommendationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-missing-event-weather-recommendations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate missing weather recommendations for events that are within 3 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        PRFEvent::query()
            ->where('start_date', '>=', now())
            ->chunk(10, function ($missions) {
                foreach ($missions as $mission) {
                    $diffInDays = $mission->start_date->diffInDays(now());
                    if ($diffInDays < 3) {
                        Bus::chain([
                            new GenerateWeatherForecastJob($mission),
                            new GenerateWeatherRecommendationsJob($mission),
                        ])
                            ->dispatch();
                    }
                }
            });
    }
}
