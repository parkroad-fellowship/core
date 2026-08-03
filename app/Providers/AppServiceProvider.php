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
use App\Events\MissionSubscription\CreatedEvent;
use App\Listeners\MissionSubscription\CreatedListener;
use App\Models\AppSetting;
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
use App\Policies\EventPolicy;
use App\Services\AI\GeminiAIService;
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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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
        $this->app->singleton(SMSGatewayInterface::class, SMSManager::class);
        $this->app->bind(PaymentGatewayInterface::class, PaystackGateway::class);
        $this->app->bind(NLPServiceInterface::class, DefaultNLPService::class);
        $this->app->bind(WeatherServiceInterface::class, TomorrowIOWeatherService::class);
        $this->app->bind(AIServiceInterface::class, GeminiAIService::class);
        $this->app->bind(MapsServiceInterface::class, GoogleMapsService::class);
        $this->app->bind(SpeechToTextServiceInterface::class, AzureSpeechService::class);
        $this->app->bind(GoogleSheetsInterface::class, GoogleSheetsService::class);
        $this->app->bind(GoogleDriveInterface::class, GoogleDriveService::class);
        $this->app->singleton(FirebaseManagerInterface::class, TenantFirebaseFactory::class);
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
            // Organization settings
            'prf.app.global_group' => 'All',
            'prf.app.excluded_emails' => [],
            'prf.app.head_office.latitude' => '-1.2906674',
            'prf.app.head_office.longitude' => '36.7690094',

            // Desk emails
            'prf.app.missions_desk.emails' => [],
            'prf.app.chairpersons_desk.emails' => [],
            'prf.app.treasurers_desk.emails' => [],
            'prf.app.prayer_desk.emails' => [],
            'prf.app.follow_up_desk.emails' => [],
            'prf.app.music_desk.emails' => [],
            'prf.app.organising_secretary_desk.emails' => [],
            'prf.app.vice_chairpersons_desk.emails' => [],

            // App stores
            'prf.app.app_stores.android.url' => '',
            'prf.app.app_stores.ios.url' => '',
            'prf.app.app_stores.huawei.url' => '',
            'prf.app.app_stores.huawei.app_id' => '',
            'prf.app.leadership_app.android.url' => '',

            // Africa's Talking
            'prf.app.africas_talking.callback_url' => '',
            'prf.app.africas_talking.from' => '',
            'prf.app.africas_talking.missions_desk' => '',
            'prf.app.africas_talking.os_desk' => '',

            // Organization
            'prf.app.executive_committee.roles' => [],
            'prf.app.camp_committee.emails' => [],
            'prf.app.telescope_emails' => CentralSetting::get('organization.telescope_emails', []),

            // SMS (default provider)
            'prf.sms.default' => env('SMS_PROVIDER', 'advanta'),

            // SMS (Advanta)
            'prf.sms.advanta.base_url' => env('ADVANTA_BASE_URL'),
            'prf.sms.advanta.api_key' => env('ADVANTA_API_KEY'),
            'prf.sms.advanta.partner_id' => env('ADVANTA_PARTNER_ID'),
            'prf.sms.advanta.short_code' => env('ADVANTA_SHORT_CODE'),

            // SMS (Africa's Talking)
            'prf.africas_talking.username' => env('AFRICAS_TALKING_USERNAME'),
            'prf.africas_talking.api_key' => env('AFRICAS_TALKING_API_KEY'),

            // Payments (Paystack)
            'prf.payments.paystack.public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'prf.payments.paystack.secret_key' => env('PAYSTACK_SECRET_KEY'),
            'prf.payments.paystack.base_url' => env('PAYSTACK_API_URL', 'https://api.paystack.co'),
            'prf.payments.paystack.callback_url' => env('PAYSTACK_CALLBACK_URL', ''),

            // NLP
            'prf.nlp.base_url' => env('PRF_NLP_BASE_URL', 'http://localhost:8005'),
            'prf.nlp.api_key' => env('PRF_NLP_API_KEY'),
            'prf.nlp.default_bot' => env('PRF_NLP_DEFAULT_BOT', 'Fridah'),

            // Weather (Tomorrow.io)
            'prf.weather.api.url' => env('WEATHER_API_URL', 'https://api.tomorrow.io/v4'),
            'prf.weather.api.apiKey' => env('WEATHER_API_KEY'),
            'prf.weather.api.units' => env('WEATHER_API_UNITS', 'metric'),

            // AI (Gemini)
            'prf.app.gemini.model' => env('GEMINI_MODEL', 'models/gemini-3-pro-preview'),
            'prf.app.gemini.api_key' => env('GEMINI_API_KEY'),
            'prf.app.gemini.max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 16384),

            // Google Maps
            'prf.app.google_maps.api_key' => env('GOOGLE_MAPS_API_KEY'),

            // Azure Speech
            'prf.app.azure_speech.subscription_key' => env('AZURE_SPEECH_SUBSCRIPTION_KEY'),
            'prf.app.azure_speech.region' => env('AZURE_SPEECH_REGION', 'southafricanorth'),

            // Google Sheets
            'prf.hooks.google_sheets.service_account_key_path' => env('GOOGLE_SERVICE_ACCOUNT_KEY_PATH'),
            'prf.hooks.google_sheets.spreadsheet_id' => env('GOOGLE_SHEETS_SOCIAL_MEDIA_SPREADSHEET_ID'),
            'prf.hooks.google_sheets.sheet_name' => env('GOOGLE_SHEETS_SOCIAL_MEDIA_SHEET_NAME'),

            // Google Drive
            'prf.hooks.google_drive.folder_id' => env('GOOGLE_DRIVE_MISSIONS_FOLDER_ID'),
            'prf.hooks.google_drive.shared_drive_id' => env('GOOGLE_DRIVE_SHARED_DRIVE_ID'),
        ]);
    }

    private function loadTenantSettings(): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        try {
            config([
                // Organization settings
                'prf.app.global_group' => AppSetting::get('general.global_group', 'All'),
                'prf.app.excluded_emails' => AppSetting::get('organization.excluded_emails', []),
                'prf.app.head_office.latitude' => AppSetting::get('organization.head_office_latitude', '-1.2906674'),
                'prf.app.head_office.longitude' => AppSetting::get('organization.head_office_longitude', '36.7690094'),

                // Desk emails
                'prf.app.missions_desk.emails' => AppSetting::get('desk_emails.missions', []),
                'prf.app.chairpersons_desk.emails' => AppSetting::get('desk_emails.chairpersons', []),
                'prf.app.treasurers_desk.emails' => AppSetting::get('desk_emails.treasurers', []),
                'prf.app.prayer_desk.emails' => AppSetting::get('desk_emails.prayer', []),
                'prf.app.follow_up_desk.emails' => AppSetting::get('desk_emails.follow_up', []),
                'prf.app.music_desk.emails' => AppSetting::get('desk_emails.music', []),
                'prf.app.organising_secretary_desk.emails' => AppSetting::get('desk_emails.organising_secretary', []),
                'prf.app.vice_chairpersons_desk.emails' => AppSetting::get('desk_emails.vice_chairpersons', []),

                // App stores
                'prf.app.app_stores.android.url' => AppSetting::get('app_stores.android_url', ''),
                'prf.app.app_stores.ios.url' => AppSetting::get('app_stores.ios_url', ''),
                'prf.app.app_stores.huawei.url' => AppSetting::get('app_stores.huawei_url', ''),
                'prf.app.leadership_app.android.url' => AppSetting::get('app_stores.leadership_android_url', ''),
                'prf.app.leadership_app.ios.url' => AppSetting::get('app_stores.leadership_ios_url', ''),

                // Africa's Talking
                'prf.app.africas_talking.callback_url' => AppSetting::get('africas_talking.callback_url', ''),
                'prf.app.africas_talking.from' => AppSetting::get('africas_talking.from', ''),
                'prf.app.africas_talking.missions_desk' => AppSetting::get('africas_talking.missions_desk', ''),
                'prf.app.africas_talking.os_desk' => AppSetting::get('africas_talking.os_desk', ''),

                // Organization
                'prf.app.executive_committee.roles' => AppSetting::get('general.executive_committee_roles', []),
                'prf.app.camp_committee.emails' => [],
                'prf.app.telescope_emails' => AppSetting::get('organization.telescope_emails', []),

                // SMS (Advanta)
                'prf.sms.advanta.base_url' => AppSetting::get('sms.advanta_base_url', env('ADVANTA_BASE_URL')),
                'prf.sms.advanta.api_key' => AppSetting::get('sms.advanta_api_key', env('ADVANTA_API_KEY')),
                'prf.sms.advanta.partner_id' => AppSetting::get('sms.advanta_partner_id', env('ADVANTA_PARTNER_ID')),
                'prf.sms.advanta.short_code' => AppSetting::get('sms.advanta_short_code', env('ADVANTA_SHORT_CODE')),

                // SMS (Africa's Talking)
                'prf.sms.default' => AppSetting::get('sms.default', env('SMS_PROVIDER', 'advanta')),
                'prf.africas_talking.username' => AppSetting::get('africas_talking.username', env('AFRICAS_TALKING_USERNAME')),
                'prf.africas_talking.api_key' => AppSetting::get('africas_talking.api_key', env('AFRICAS_TALKING_API_KEY')),

                // Payments (Paystack)
                'prf.payments.paystack.public_key' => AppSetting::get('payments.paystack_public_key', env('PAYSTACK_PUBLIC_KEY')),
                'prf.payments.paystack.secret_key' => AppSetting::get('payments.paystack_secret_key', env('PAYSTACK_SECRET_KEY')),
                'prf.payments.paystack.base_url' => AppSetting::get('payments.paystack_base_url', env('PAYSTACK_API_URL', 'https://api.paystack.co')),
                'prf.payments.paystack.callback_url' => AppSetting::get('payments.paystack_callback_url', env('PAYSTACK_CALLBACK_URL', '')),

                // NLP
                'prf.nlp.base_url' => AppSetting::get('nlp.base_url', env('PRF_NLP_BASE_URL', 'http://localhost:8005')),
                'prf.nlp.api_key' => AppSetting::get('nlp.api_key', env('PRF_NLP_API_KEY')),
                'prf.nlp.default_bot' => AppSetting::get('nlp.default_bot', env('PRF_NLP_DEFAULT_BOT', 'Fridah')),

                // Weather (Tomorrow.io)
                'prf.weather.api.url' => AppSetting::get('weather.api_url', env('WEATHER_API_URL', 'https://api.tomorrow.io/v4')),
                'prf.weather.api.apiKey' => AppSetting::get('weather.api_key', env('WEATHER_API_KEY')),
                'prf.weather.api.units' => AppSetting::get('weather.api_units', env('WEATHER_API_UNITS', 'metric')),

                // AI (Gemini)
                'prf.app.gemini.model' => AppSetting::get('gemini.model', env('GEMINI_MODEL', 'models/gemini-3-pro-preview')),
                'prf.app.gemini.api_key' => AppSetting::get('gemini.api_key', env('GEMINI_API_KEY')),
                'prf.app.gemini.max_output_tokens' => (int) AppSetting::get('gemini.max_output_tokens', env('GEMINI_MAX_OUTPUT_TOKENS', 16384)),

                // Google Maps
                'prf.app.google_maps.api_key' => AppSetting::get('google_maps.api_key', env('GOOGLE_MAPS_API_KEY')),

                // Azure Speech
                'prf.app.azure_speech.subscription_key' => AppSetting::get('azure_speech.subscription_key', env('AZURE_SPEECH_SUBSCRIPTION_KEY')),
                'prf.app.azure_speech.region' => AppSetting::get('azure_speech.region', env('AZURE_SPEECH_REGION', 'southafricanorth')),

                // Google Sheets
                'prf.hooks.google_sheets.service_account_key_path' => AppSetting::get('google_sheets.service_account_key_path', env('GOOGLE_SERVICE_ACCOUNT_KEY_PATH')),
                'prf.hooks.google_sheets.spreadsheet_id' => AppSetting::get('google_sheets.spreadsheet_id', env('GOOGLE_SHEETS_SOCIAL_MEDIA_SPREADSHEET_ID')),
                'prf.hooks.google_sheets.sheet_name' => AppSetting::get('google_sheets.sheet_name', env('GOOGLE_SHEETS_SOCIAL_MEDIA_SHEET_NAME')),

                // Google Drive
                'prf.hooks.google_drive.folder_id' => AppSetting::get('google_drive.folder_id', env('GOOGLE_DRIVE_MISSIONS_FOLDER_ID')),
                'prf.hooks.google_drive.shared_drive_id' => AppSetting::get('google_drive.shared_drive_id', env('GOOGLE_DRIVE_SHARED_DRIVE_ID')),
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
