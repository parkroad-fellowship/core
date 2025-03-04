<?php

use App\Console\Commands\Mission\GenerateMissingWeatherRecommendationsCommand;
use App\Console\Commands\Payment\CheckStatusCommand;
use Illuminate\Support\Facades\Schedule;

// Schedule missing weather recommendations for missions that are within 3 days to run daily at midnight
Schedule::command(GenerateMissingWeatherRecommendationsCommand::class)
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(\App\Console\Commands\PRFEvent\GenerateMissingWeatherRecommendationsCommand::class)
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(CheckStatusCommand::class)
    ->everyThreeMinutes()
    ->withoutOverlapping()
    ->onOneServer();
