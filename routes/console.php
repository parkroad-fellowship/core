<?php

use App\Console\Commands\Payment\PollPaymentStatusCommand;
use App\Console\Commands\Pledge\DispatchDueRemindersCommand;
use App\Console\Commands\Pledge\ReconcilePaymentsCommand;
use Illuminate\Support\Facades\Schedule;

// Every command below that touches tenant data runs once per tenant (RunsForEachTenant).

// Pledge reminders, once a day (the reminder ledger prevents duplicates).
Schedule::command(DispatchDueRemindersCommand::class)->daily()->withoutOverlapping()->onOneServer();

// Safety net for matching successful payments to pledges (normally done as each payment succeeds).
Schedule::command(ReconcilePaymentsCommand::class)->hourly()->withoutOverlapping()->onOneServer();

// Weather forecasts for missions and events starting within the next 3 days, daily at 05:00.
Schedule::command(\App\Console\Commands\Mission\GenerateMissingWeatherRecommendationsCommand::class)
    ->dailyAt('05:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(\App\Console\Commands\PRFEvent\GenerateMissingWeatherRecommendationsCommand::class)
    ->dailyAt('05:00')
    ->withoutOverlapping()
    ->onOneServer();

// Webhooks are the primary signal; polling is a bounded fallback (see PollPaymentStatusCommand).
Schedule::command(PollPaymentStatusCommand::class)->everyFiveMinutes()->withoutOverlapping()->onOneServer();

Schedule::command('telescope:prune --hours=48')->daily()->environments(['production']);
Schedule::command('telescope:prune --hours=12')->daily()->environments(['staging', 'development']);

// Database backup twice a day, at 00:00 and 12:00.
Schedule::command('backup:run --only-db')->withoutOverlapping()->onOneServer()->twiceDailyAt(0, 12);

// Clean old backups twice a day, at 01:00 and 13:00.
Schedule::command('backup:clean')->withoutOverlapping()->onOneServer()->twiceDailyAt(1, 13);
