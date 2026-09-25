<?php

namespace App\Providers;

use App\Contracts\Services\AIServiceInterface;
use App\Contracts\Services\FirebaseManagerInterface;
use App\Contracts\Services\GoogleDriveInterface;
use App\Contracts\Services\GoogleSheetsInterface;
use App\Contracts\Services\MapsServiceInterface;
use App\Contracts\Services\NLPServiceInterface;
use App\Contracts\Services\PaymentGatewayInterface;
use App\Contracts\Services\SMSGatewayInterface;
use App\Contracts\Services\SpeechToTextServiceInterface;
use App\Contracts\Services\WeatherServiceInterface;
use App\Enums\PRFMorphType;
use App\Enums\PRFRole;
use App\Models\CentralSetting;
use App\Models\ChatBot;
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
use App\Services\AI\LaravelAIService;
use App\Services\Firebase\TenantFirebaseFactory;
use App\Services\GoogleDriveService;
use App\Services\GoogleSheetsService;
use App\Services\Maps\GoogleMapsService;
use App\Services\NLP\DefaultNLPService;
use App\Services\Payments\PaystackGateway;
use App\Services\SMS\SMSManager;
use App\Services\SpeechToText\AzureSpeechService;
use App\Services\Weather\TomorrowIOWeatherService;
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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Stancl\Tenancy\Bootstrappers\PostgresRLSBootstrapper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\Services\WorkspaceDirectoryInterface::class,
            \App\Services\Google\Workspace\GoogleWorkspaceDirectory::class,
        );
        $this->app->singleton(SMSManager::class);
        $this->app->alias(SMSManager::class, SMSGatewayInterface::class);
        $this->app->bind(PaymentGatewayInterface::class, PaystackGateway::class);
        $this->app->bind(NLPServiceInterface::class, DefaultNLPService::class);
        $this->app->bind(WeatherServiceInterface::class, TomorrowIOWeatherService::class);
        $this->app->bind(AIServiceInterface::class, LaravelAIService::class);
        $this->app->bind(MapsServiceInterface::class, GoogleMapsService::class);
        $this->app->bind(SpeechToTextServiceInterface::class, AzureSpeechService::class);
        $this->app->bind(GoogleSheetsInterface::class, GoogleSheetsService::class);
        $this->app->bind(GoogleDriveInterface::class, GoogleDriveService::class);
        $this->app->singleton(FirebaseManagerInterface::class, TenantFirebaseFactory::class);
        // Push notifications must use the current tenant's Firebase project, never the platform one.
        $this->app->bind(\Kreait\Firebase\Contract\Messaging::class, fn($app) => $app->make(FirebaseManagerInterface::class)->messaging());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        \Illuminate\Support\Facades\Blade::directive('tenantAsset', function ($expression) {
            return "<?php echo e(tenant_asset({$expression})); ?>";
        });

        \Illuminate\Support\Facades\View::composer('*', \App\Http\View\Composers\TenantAssetViewComposer::class);

        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole(PRFRole::SUPER_ADMIN) && (bool) config('pulse.enabled');
        });

        RateLimiter::for('api', function (Request $request) {
            $authenticatedUserId = $request->user()?->id;

            // TODO: Figure out a better way
            return Limit::perMinute(200)->by(
                $authenticatedUserId
                    ? "user:{$authenticatedUserId}"
                    : 'ip:' . $this->resolveRateLimitClientIp($request),
            );
        });

        RateLimiter::for('api-auth', function (Request $request) {
            $email = Str::lower((string) $request->input('email', 'guest'));

            // TODO: Figure out a better way
            return Limit::perMinute(200)->by("{$email}|ip:{$this->resolveRateLimitClientIp($request)}");
        });

        // Outbound Paystack status checks, per tenant (each tenant has its own Paystack account).
        RateLimiter::for('paystack', fn(object $job) => Limit::perMinute(60)->by('paystack:' . tenant('id')));

        RateLimiter::for('api-webhook', function (Request $request) {
            return Limit::perMinute(120)->by('ip:' . $this->resolveRateLimitClientIp($request));
        });

        if (!App::environment('local')) {
            URL::forceScheme('https');
        }

        ExportAction::configureUsing(fn(ExportAction $action) => $action->fileDisk(config('filesystems.default')));
        DateTimePicker::configureUsing(fn(DateTimePicker $component) => $component->timezone(
            Auth::user()?->timezone ?? config('app.timezone'),
        ));
        DatePicker::configureUsing(fn(DatePicker $component) => $component->timezone(config('app.timezone'))); // Need to use app timezone here to avoid issues with date-only fields being off by one day when user timezone is ahead of UTC
        TimePicker::configureUsing(fn(TimePicker $component) => $component->timezone(
            Auth::user()?->timezone ?? config('app.timezone'),
        ));

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

        $this->loadCentralDefaults();

        // RLS bootstrapper swaps connection credentials on tenant init, which
        // splits RefreshDatabase's transaction across sessions in tests and
        // deadlocks the suite. Tests run single-identity instead.
        if (DB::connection()->getDriverName() === 'pgsql' && !app()->environment('testing')) {
            config([
                'tenancy.bootstrappers' => array_merge(
                    config('tenancy.bootstrappers', []),
                    [PostgresRLSBootstrapper::class],
                ),
            ]);
        }

        Livewire::setUpdateRoute(function ($handle, $path) {
            return Route::post($path, $handle)->middleware([
                'web',
                \App\Http\Middleware\ConditionalTenancyMiddleware::class,
            ]);
        });
    }

    /**
     * Central-context defaults that come from the central database rather than config files.
     */
    private function loadCentralDefaults(): void
    {
        config([
            'prf.app.telescope_emails' => rescue(
                fn() => CentralSetting::get('organization.telescope_emails', []),
                config('prf.app.telescope_emails', []),
                report: false,
            ),
        ]);
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
