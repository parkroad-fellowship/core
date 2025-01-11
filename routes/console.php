<?php

use App\Console\Commands\Mission\GenerateMissingWeatherRecommendationsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Schedule missing weather recommendations for missions that are within 3 days to run daily at midnight
Schedule::command(GenerateMissingWeatherRecommendationsCommand::class)
    ->dailyAt('00:00');
