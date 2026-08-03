<?php

use App\Contracts\Services\AiServiceInterface;
use App\Contracts\Services\GoogleDriveInterface;
use App\Contracts\Services\GoogleSheetsInterface;
use App\Contracts\Services\MapsServiceInterface;
use App\Contracts\Services\NlpServiceInterface;
use App\Contracts\Services\PaymentGatewayInterface;
use App\Contracts\Services\SMSGatewayInterface as SmsGatewayInterface;
use App\Contracts\Services\SpeechToTextServiceInterface;
use App\Contracts\Services\WeatherServiceInterface;
use App\Services\Ai\GeminiAiService;
use App\Services\GoogleDriveService;
use App\Services\GoogleSheetsService;
use App\Services\Maps\GoogleMapsService;
use App\Services\Nlp\DefaultNlpService;
use App\Services\Payments\PaystackGateway;
use App\Services\Sms\AdvantaSmsGateway;
use App\Services\Sms\AfricasTalkingSmsGateway;
use App\Services\SpeechToText\AzureSpeechService;
use App\Services\Weather\TomorrowIoWeatherService;

it('binds all service interfaces to their default implementations', function () {
    expect(app(SmsGatewayInterface::class))->toBeInstanceOf(AdvantaSmsGateway::class)
        ->and(app(PaymentGatewayInterface::class))->toBeInstanceOf(PaystackGateway::class)
        ->and(app(NlpServiceInterface::class))->toBeInstanceOf(DefaultNlpService::class)
        ->and(app(WeatherServiceInterface::class))->toBeInstanceOf(TomorrowIoWeatherService::class)
        ->and(app(AiServiceInterface::class))->toBeInstanceOf(GeminiAiService::class)
        ->and(app(MapsServiceInterface::class))->toBeInstanceOf(GoogleMapsService::class)
        ->and(app(SpeechToTextServiceInterface::class))->toBeInstanceOf(AzureSpeechService::class)
        ->and(app(GoogleSheetsInterface::class))->toBeInstanceOf(GoogleSheetsService::class)
        ->and(app(GoogleDriveInterface::class))->toBeInstanceOf(GoogleDriveService::class);
});

use Illuminate\Database\Eloquent\Model;

it('allows overriding a service interface binding', function () {
    $customSms = new class implements SmsGatewayInterface
    {
        public function send(string $phoneNumber, string $message, ?Model $smsLoggable = null): array
        {
            return ['message_id' => 'custom-123', 'response' => []];
        }

        public function checkBlacklist(string $messageId): bool
        {
            return false;
        }
    };

    app()->bind(SmsGatewayInterface::class, fn () => $customSms);

    $sms = app(SmsGatewayInterface::class);
    expect($sms)->toBe($customSms)
        ->and($sms->send('+1234567890', 'test'))->toBe(['message_id' => 'custom-123', 'response' => []]);
});

it('resolves SMS config from safe defaults', function () {
    expect(config('prf.sms.advanta.base_url'))->not->toBeNull()
        ->and(config('prf.sms.advanta.api_key'))->not->toBeNull()
        ->and(config('prf.sms.advanta.partner_id'))->not->toBeNull()
        ->and(config('prf.sms.advanta.short_code'))->not->toBeNull();
});

it('resolves payment config from safe defaults', function () {
    expect(config('prf.payments.paystack.secret_key'))->not->toBeNull()
        ->and(config('prf.payments.paystack.base_url'))->not->toBeNull()
        ->and(config('prf.payments.paystack.callback_url'))->not->toBeNull();
});

it('resolves NLP config from safe defaults', function () {
    expect(config('prf.nlp.base_url'))->not->toBeNull()
        ->and(config('prf.nlp.api_key'))->not->toBeNull()
        ->and(config('prf.nlp.default_bot'))->not->toBeNull();
});

it('resolves weather config from safe defaults', function () {
    expect(config('prf.weather.api.url'))->not->toBeNull()
        ->and(config('prf.weather.api.apiKey'))->not->toBeNull()
        ->and(config('prf.weather.api.units'))->not->toBeNull();
});

it('resolves Gemini config from safe defaults', function () {
    expect(config('prf.app.gemini.api_key'))->not->toBeNull()
        ->and(config('prf.app.gemini.model'))->not->toBeNull();
});

it('resolves Google Maps config from safe defaults', function () {
    expect(config('prf.app.google_maps.api_key'))->not->toBeNull();
});

it('resolves Azure Speech config from safe defaults', function () {
    expect(config('prf.app.azure_speech.subscription_key'))->not->toBeNull()
        ->and(config('prf.app.azure_speech.region'))->not->toBeNull();
});

it('resolves Google Sheets config from safe defaults', function () {
    expect(config('prf.hooks.google_sheets.spreadsheet_id'))->not->toBeNull()
        ->and(config('prf.hooks.google_sheets.sheet_name'))->not->toBeNull();
});

it('resolves Google Drive config from safe defaults', function () {
    expect(config('prf.hooks.google_drive.folder_id'))->not->toBeNull()
        ->and(config('prf.hooks.google_drive.shared_drive_id'))->not->toBeNull();
});

it('resolves Africa\'s Talking config from safe defaults', function () {
    expect(config('prf.africas_talking.username'))->not->toBeNull()
        ->and(config('prf.africas_talking.api_key'))->not->toBeNull()
        ->and(config('prf.sms.default'))->not->toBeNull();
});

it('binds AfricasTalkingSmsGateway when SMS provider is africas_talking', function () {
    config(['prf.sms.default' => 'africas_talking']);

    expect(app(SmsGatewayInterface::class))->toBeInstanceOf(AfricasTalkingSmsGateway::class);
});

it('binds AdvantaSmsGateway when SMS provider is advanta', function () {
    config(['prf.sms.default' => 'advanta']);

    expect(app(SmsGatewayInterface::class))->toBeInstanceOf(AdvantaSmsGateway::class);
});

it('binds AdvantaSmsGateway as default when SMS provider is not set', function () {
    config(['prf.sms.default' => null]);

    expect(app(SmsGatewayInterface::class))->toBeInstanceOf(AdvantaSmsGateway::class);
});
