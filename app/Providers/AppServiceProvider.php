<?php

namespace App\Providers;

use App\Enums\PRFMorphType;
use App\Events\MissionSubscription\CreatedEvent;
use App\Listeners\MissionSubscription\CreatedListener;
use App\Models\AppSetting;
use App\Models\ChatBot;
use App\Models\ConnectedAccount;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionExpense;
use App\Models\MissionQuestion;
use App\Models\MissionSession;
use App\Models\PersonalAccessToken;
use App\Models\PRFEvent;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Policies\EventPolicy;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use JoelButcher\Socialstream\Socialstream;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Stancl\Tenancy\Bootstrappers\PostgresRLSBootstrapper;
use Stancl\Tenancy\Events\TenancyInitialized;

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
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        Socialstream::$connectedAccountModel = ConnectedAccount::class;

        Gate::policy(PRFEvent::class, EventPolicy::class);

        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole('super admin') && (bool) config('pulse.enabled');
        });

        RateLimiter::for('api', function (Request $request) {
            $authenticatedUserId = $request->user()?->id;

            // TODO: Figure out a better way
            return Limit::perMinute(200)->by($authenticatedUserId
                ? "user:{$authenticatedUserId}"
                : 'ip:'.$this->resolveRateLimitClientIp($request));
        });

        RateLimiter::for('api-auth', function (Request $request) {
            $email = Str::lower((string) $request->input('email', 'guest'));

            // TODO: Figure out a better way
            return Limit::perMinute(200)->by("{$email}|ip:{$this->resolveRateLimitClientIp($request)}");
        });

        RateLimiter::for('api-webhook', function (Request $request) {
            return Limit::perMinute(30)->by('ip:'.$this->resolveRateLimitClientIp($request));
        });

        if (! App::environment('local')) {
            URL::forceScheme('https');
        }

        ExportAction::configureUsing(fn (ExportAction $action) => $action->fileDisk('local'));
        DateTimePicker::configureUsing(fn (DateTimePicker $component) => $component->timezone(Auth::user()?->timezone ?? config('app.timezone')));
        DatePicker::configureUsing(fn (DatePicker $component) => $component->timezone(config('app.timezone'))); // Need to use app timezone here to avoid issues with date-only fields being off by one day when user timezone is ahead of UTC
        TimePicker::configureUsing(fn (TimePicker $component) => $component->timezone(Auth::user()?->timezone ?? config('app.timezone')));

        Relation::morphMap([
            PRFMorphType::MEMBER->value => Member::class,
            PRFMorphType::STUDENT->value => Student::class,

            PRFMorphType::MISSION_EXPENSE->value => MissionExpense::class,

            PRFMorphType::EVENT->value => PRFEvent::class,
            PRFMorphType::MISSION->value => Mission::class,

            PRFMorphType::CHAT_BOT->value => ChatBot::class,

            PRFMorphType::SCHOOL->value => School::class,

            PRFMorphType::MISSION_SESSION->value => MissionSession::class,
            PRFMorphType::MISSION_QUESTION->value => MissionQuestion::class,
        ]);

        Event::listen(
            CreatedEvent::class,
            CreatedListener::class,
        );

        $this->loadSafeDefaults();

        if (DB::connection()->getDriverName() === 'pgsql') {
            config([
                'tenancy.bootstrappers' => array_merge(
                    config('tenancy.bootstrappers', []),
                    [PostgresRLSBootstrapper::class],
                ),
            ]);
        }

        Event::listen(TenancyInitialized::class, function () {
            $this->loadTenantSettings();
        });

        Livewire::setUpdateRoute(function ($handle, $path) {
            return Route::post($path, $handle)->middleware([
                'web',
                \App\Http\Middleware\ConditionalTenancyMiddleware::class,
            ]);
        });
    }

    private function loadSafeDefaults(): void
    {
        config([
            'prf.app.global_group' => 'All',
            'prf.app.excluded_emails' => [],
            'prf.app.head_office.latitude' => '-1.2906674',
            'prf.app.head_office.longitude' => '36.7690094',
            'prf.app.missions_desk.emails' => [],
            'prf.app.chairpersons_desk.emails' => [],
            'prf.app.treasurers_desk.emails' => [],
            'prf.app.prayer_desk.emails' => [],
            'prf.app.follow_up_desk.emails' => [],
            'prf.app.music_desk.emails' => [],
            'prf.app.organising_secretary_desk.emails' => [],
            'prf.app.vice_chairpersons_desk.emails' => [],
            'prf.app.app_stores.android.url' => '',
            'prf.app.app_stores.ios.url' => '',
            'prf.app.app_stores.huawei.url' => '',
            'prf.app.app_stores.huawei.app_id' => '',
            'prf.app.leadership_app.android.url' => '',
            'prf.app.africas_talking.callback_url' => '',
            'prf.app.africas_talking.from' => '',
            'prf.app.africas_talking.missions_desk' => '',
            'prf.app.africas_talking.os_desk' => '',
            'prf.app.executive_committee.roles' => [],
            'prf.app.camp_committee.emails' => [],
        ]);
    }

    private function loadTenantSettings(): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        try {
            config([
                'prf.app.global_group' => AppSetting::get('general.global_group', 'All'),
                'prf.app.excluded_emails' => AppSetting::get('organization.excluded_emails', []),
                'prf.app.head_office.latitude' => AppSetting::get('organization.head_office_latitude', '-1.2906674'),
                'prf.app.head_office.longitude' => AppSetting::get('organization.head_office_longitude', '36.7690094'),
                'prf.app.missions_desk.emails' => AppSetting::get('desk_emails.missions', []),
                'prf.app.chairpersons_desk.emails' => AppSetting::get('desk_emails.chairpersons', []),
                'prf.app.treasurers_desk.emails' => AppSetting::get('desk_emails.treasurers', []),
                'prf.app.prayer_desk.emails' => AppSetting::get('desk_emails.prayer', []),
                'prf.app.follow_up_desk.emails' => AppSetting::get('desk_emails.follow_up', []),
                'prf.app.music_desk.emails' => AppSetting::get('desk_emails.music', []),
                'prf.app.organising_secretary_desk.emails' => AppSetting::get('desk_emails.organising_secretary', []),
                'prf.app.vice_chairpersons_desk.emails' => AppSetting::get('desk_emails.vice_chairpersons', []),
                'prf.app.app_stores.android.url' => AppSetting::get('app_stores.android_url', ''),
                'prf.app.app_stores.ios.url' => AppSetting::get('app_stores.ios_url', ''),
                'prf.app.app_stores.huawei.url' => AppSetting::get('app_stores.huawei_url', ''),
                'prf.app.leadership_app.android.url' => AppSetting::get('app_stores.leadership_android_url', ''),
                'prf.app.leadership_app.ios.url' => AppSetting::get('app_stores.leadership_ios_url', ''),
                'prf.app.africas_talking.callback_url' => AppSetting::get('africas_talking.callback_url', ''),
                'prf.app.africas_talking.from' => AppSetting::get('africas_talking.from', ''),
                'prf.app.africas_talking.missions_desk' => AppSetting::get('africas_talking.missions_desk', ''),
                'prf.app.africas_talking.os_desk' => AppSetting::get('africas_talking.os_desk', ''),
                'prf.app.executive_committee.roles' => AppSetting::get('general.executive_committee_roles', []),
                'prf.app.camp_committee.emails' => [],
                'prf.app.telescope_emails' => AppSetting::get('organization.telescope_emails', []),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to load tenant settings', ['error' => $e->getMessage()]);
        }
    }

    private function resolveRateLimitClientIp(Request $request): string
    {
        $cloudflareConnectingIp = trim((string) $request->header('CF-Connecting-IP', ''));

        if ($cloudflareConnectingIp !== '') {
            return $cloudflareConnectingIp;
        }

        $xForwardedFor = trim((string) $request->header('X-Forwarded-For', ''));

        if ($xForwardedFor !== '') {
            $forwardedIps = array_filter(array_map('trim', explode(',', $xForwardedFor)));

            if ($forwardedIps !== []) {
                return (string) reset($forwardedIps);
            }
        }

        return (string) ($request->ip() ?? 'unknown');
    }
}
