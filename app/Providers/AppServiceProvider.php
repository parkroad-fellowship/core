<?php

namespace App\Providers;

use App\Enums\PRFMorphType;
use App\Models\Member;
use App\Models\MissionExpense;
use App\Models\Student;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
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
        // if (App::isProduction()) {
        URL::forceScheme('https');
        // }

        DateTimePicker::configureUsing(fn(DateTimePicker $component) => $component->timezone(Auth::user()?->timezone ?? config('app.timezone')));
        TimePicker::configureUsing(fn(TimePicker $component) => $component->timezone(Auth::user()?->timezone ?? config('app.timezone')));

        Relation::morphMap([
            PRFMorphType::MEMBER->value => Member::class,
            PRFMorphType::STUDENT->value => Student::class,

            PRFMorphType::MISSION_EXPENSE->value => MissionExpense::class,
        ]);
    }
}
