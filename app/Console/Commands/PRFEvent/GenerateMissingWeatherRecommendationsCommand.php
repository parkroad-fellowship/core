<?php

namespace App\Console\Commands\PRFEvent;

use App\Console\Concerns\RunsForEachTenant;
use App\Jobs\PRFEvent\GenerateWeatherForecastJob;
use App\Jobs\PRFEvent\GenerateWeatherRecommendationsJob;
use App\Models\PRFEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class GenerateMissingWeatherRecommendationsCommand extends Command
{
    use RunsForEachTenant;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prf:events:generate-missing-weather-recommendations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate missing weather recommendations for events that are within 3 days';

    public function handle(): int
    {
        $this->forEachTenant(fn() => PRFEvent::query()
            ->where('start_date', '>=', now()->startOfDay())
            ->lazyById()
            ->each(function (PRFEvent $event): void {
                $daysUntilStart = now()->diffInDays($event->start_date, absolute: false);

                if ($daysUntilStart >= 0 && $daysUntilStart < 3) {
                    Bus::chain([
                        new GenerateWeatherForecastJob($event),
                        new GenerateWeatherRecommendationsJob($event),
                    ])->dispatch();
                }
            }));

        return self::SUCCESS;
    }
}
