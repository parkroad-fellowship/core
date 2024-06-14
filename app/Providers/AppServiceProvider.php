<?php

namespace App\Providers;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (App::isProduction()) {
            URL::forceScheme('https');
        }

        DateTimePicker::configureUsing(fn (DateTimePicker $component) => $component->timezone(auth()->user()?->timezone ?? config('app.timezone')));
        TimePicker::configureUsing(fn (TimePicker $component) => $component->timezone(auth()->user()?->timezone ?? config('app.timezone')));
    }
}
