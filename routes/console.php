<?php

use App\Console\Commands\Mission\GenerateMissingWeatherRecommendationsCommand;
use App\Console\Commands\Payment\CheckStatusCommand;
use App\Console\Commands\Pledge\DispatchDueRemindersCommand;
use App\Console\Commands\Pledge\ReconcilePaymentsCommand;
use Illuminate\Support\Facades\Schedule;

// Send pledge due-due reminders daily (dedupe-safe via the reminder ledger).
Schedule::command(DispatchDueRemindersCommand::class)->daily()->withoutOverlapping()->onOneServer();

// Match successful payments to pledges throughout the day.
Schedule::command(ReconcilePaymentsCommand::class)->hourly()->withoutOverlapping()->onOneServer();

// Schedule missing weather recommendations for missions that are within 3 days to run daily at midnight
Schedule::command(GenerateMissingWeatherRecommendationsCommand::class)
    ->weeklyOn(3, '05:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(App\Console\Commands\PRFEvent\GenerateMissingWeatherRecommendationsCommand::class)
    ->weeklyOn(3, '05:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(CheckStatusCommand::class)->everyThreeMinutes()->withoutOverlapping()->onOneServer();

Schedule::command('telescope:prune --hours=48')->daily()->environments(['production']);
Schedule::command('telescope:prune --hours=12')->daily()->environments(['staging', 'development']);

// Backup database every day at 12:00 and 13:00
Schedule::command('backup:run --only-db')->withoutOverlapping()->onOneServer()->twiceDailyAt(0, 12);

// Clean old backups every day at 1:00 and 2:00
Schedule::command('backup:clean')->withoutOverlapping()->onOneServer()->twiceDailyAt(1, 13);
