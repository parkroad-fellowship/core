<?php

namespace App\Providers;

use App\Enums\PRFMorphType;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionExpense;
use App\Models\PRFEvent;
use App\Models\Student;
use App\Models\User;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole('super admin');
        });

        if (! App::environment('local')) {
            URL::forceScheme('https');
        }

        ExportAction::configureUsing(fn (ExportAction $action) => $action->fileDisk('local'));
        DateTimePicker::configureUsing(fn (DateTimePicker $component) => $component->timezone(Auth::user()?->timezone ?? config('app.timezone')));
        TimePicker::configureUsing(fn (TimePicker $component) => $component->timezone(Auth::user()?->timezone ?? config('app.timezone')));
        DatePicker::configureUsing(fn (DatePicker $component) => $component->timezone(Auth::user()?->timezone ?? config('app.timezone')));

        Relation::morphMap([
            PRFMorphType::MEMBER->value => Member::class,
            PRFMorphType::STUDENT->value => Student::class,

            PRFMorphType::MISSION_EXPENSE->value => MissionExpense::class,

            PRFMorphType::EVENT->value => PRFEvent::class,
            PRFMorphType::MISSION->value => Mission::class,
        ]);

        Event::listen(
            \App\Events\MissionSubscription\CreatedEvent::class,
            \App\Listeners\MissionSubscription\CreatedListener::class,
        );
    }
}
