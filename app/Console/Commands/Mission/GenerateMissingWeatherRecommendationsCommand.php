<?php

namespace App\Console\Commands\Mission;

use App\Console\Concerns\RunsForEachTenant;
use App\Jobs\Mission\GenerateWeatherForecastJob;
use App\Jobs\Mission\GenerateWeatherRecommendationsJob;
use App\Models\Mission;
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
    protected $signature = 'prf:missions:generate-missing-weather-recommendations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate missing weather recommendations for missions that are within 3 days';

    public function handle(): int
    {
        $this->forEachTenant(fn() => Mission::query()
            ->where('start_date', '>=', now()->startOfDay())
            ->lazyById()
            ->each(function (Mission $mission): void {
                $daysUntilStart = now()->diffInDays($mission->start_date, absolute: false);

                if ($daysUntilStart >= 0 && $daysUntilStart < 3) {
                    Bus::chain([
                        new GenerateWeatherForecastJob($mission),
                        new GenerateWeatherRecommendationsJob($mission),
                    ])->dispatch();
                }
            }));

        return self::SUCCESS;
    }
}
