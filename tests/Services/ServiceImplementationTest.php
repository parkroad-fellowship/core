<?php

use App\Services\Ai\GeminiAiService;
use App\Services\Maps\GoogleMapsService;
use App\Services\Nlp\DefaultNlpService;
use App\Services\Payments\PaystackGateway;
use App\Services\Sms\AdvantaSmsGateway;
use App\Services\Sms\AfricasTalkingSmsGateway;
use App\Services\SpeechToText\AzureSpeechService;
use App\Services\Weather\TomorrowIoWeatherService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'prf.sms.advanta.base_url' => 'sms-api.test.com',
        'prf.sms.advanta.api_key' => 'test-api-key',
        'prf.sms.advanta.partner_id' => 'test-partner',
        'prf.sms.advanta.short_code' => 'PRF',
        'prf.sms.test_phone_number' => '+254700000000',
        'prf.payments.paystack.secret_key' => 'sk_test',
        'prf.payments.paystack.base_url' => 'https://api.paystack.co',
        'prf.payments.paystack.callback_url' => 'https://example.com/callback',
        'prf.nlp.api_key' => 'nlp-test-key',
        'prf.nlp.base_url' => 'https://nlp.test.com',
        'prf.nlp.default_bot' => 'TestBot',
        'prf.weather.api.url' => 'https://api.tomorrow.io/v4',
        'prf.weather.api.apiKey' => 'weather-test-key',
        'prf.weather.api.units' => 'metric',
        'prf.app.gemini.model' => 'models/gemini-test',
        'prf.app.gemini.api_key' => 'gemini-test-key',
        'prf.app.gemini.max_output_tokens' => 4096,
        'prf.app.google_maps.api_key' => 'maps-test-key',
        'prf.app.azure_speech.subscription_key' => 'azure-test-key',
        'prf.app.azure_speech.region' => 'test-region',
    ]);
});

it('checks blacklist via Advanta gateway', function () {
    Http::fake([
        'sms-api.test.com/api/services/getdlr' => Http::response([
            'delivery-description' => 'SenderName Blacklisted',
        ], 200),
    ]);

    $gateway = new AdvantaSmsGateway;
    $result = $gateway->checkBlacklist('msg-123');

    expect($result)->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'getdlr'));
});

it('initializes Paystack transaction', function () {
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['authorization_url' => 'https://checkout.paystack.com/test'],
        ], 200),
    ]);

    $gateway = new PaystackGateway;
    $result = $gateway->initializeTransaction([
        'email' => 'test@example.com',
        'amount' => 5000,
        'id' => 'ref-123',
    ]);

    expect($result['status'])->toBeTrue()
        ->and($result['data']['authorization_url'])->toBe('https://checkout.paystack.com/test');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'transaction/initialize'));
});

it('verifies Paystack transaction', function () {
    Http::fake([
        'api.paystack.co/transaction/verify/ref-123' => Http::response([
            'status' => true,
            'data' => ['status' => 'success', 'amount' => 5000],
        ], 200),
    ]);

    $gateway = new PaystackGateway;
    $result = $gateway->verifyTransaction('ref-123');

    expect($result['status'])->toBeTrue()
        ->and($result['data']['status'])->toBe('success');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'transaction/verify/ref-123'));
});

it('verifies Paystack webhook signature', function () {
    $gateway = new PaystackGateway;
    $payload = '{"event":"charge.success","data":{"amount":5000}}';
    $secret = config('prf.payments.paystack.secret_key');
    $signature = hash_hmac('sha512', $payload, $secret);

    $request = \Illuminate\Http\Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
    ], $payload);

    expect($gateway->verifyWebhook($request))->toBeTrue();
});

it('rejects invalid Paystack webhook signature', function () {
    $gateway = new PaystackGateway;

    $request = \Illuminate\Http\Request::create('/webhook', 'POST', [], [], [], [
        'HTTP_X_PAYSTACK_SIGNATURE' => 'invalid-signature',
    ], '{"event":"charge.success"}');

    expect($gateway->verifyWebhook($request))->toBeFalse();
});

it('embeds content via NLP service', function () {
    Http::fake([
        'nlp.test.com/embedding/init' => Http::response(['status' => 'ok'], 200),
    ]);

    $nlp = new DefaultNlpService;
    $nlp->embedContent(['test document 1', 'test document 2']);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'embedding/init')
            && str_contains($request->body(), 'test document 1');
    });
});

it('gets weather forecast from Tomorrow.io', function () {
    $forecastData = [
        'timelines' => [
            'daily' => [
                ['time' => '2026-07-23T00:00:00Z', 'values' => ['temperatureAvg' => 25]],
            ],
        ],
    ];

    Http::fake([
        'api.tomorrow.io/*' => Http::response($forecastData, 200),
    ]);

    $weather = new TomorrowIoWeatherService;
    $result = $weather->getForecast(-1.29, 36.77);

    expect($result['timelines']['daily'])->toHaveCount(1);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'weather/forecast'));
});

it('generates content via Gemini AI', function () {
    $text = '{"recommendations": []}';
    $part = ['text' => $text];
    $content = ['parts' => [$part]];
    $candidate = ['content' => $content];
    $responsePayload = ['candidates' => [$candidate]];

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response($responsePayload, 200),
    ]);

    $ai = new GeminiAiService;
    $result = $ai->generateContent('You are a helpful assistant', 'Tell me about PRF');

    expect($result)->toHaveKey('recommendations');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'generativelanguage.googleapis.com'));
});

it('computes route via Google Maps', function () {
    Http::fake([
        'routes.googleapis.com/*' => Http::response([
            'routes' => [
                ['localizedValues' => ['distance' => ['text' => '10 km'], 'staticDuration' => ['text' => '15 mins']]],
            ],
        ], 200),
    ]);

    $maps = new GoogleMapsService;
    $result = $maps->computeRoute(
        ['latitude' => -1.29, 'longitude' => 36.77],
        ['latitude' => -1.30, 'longitude' => 36.78],
    );

    expect($result['routes'][0]['localizedValues']['distance']['text'])->toBe('10 km');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'routes.googleapis.com'));
});

it('transcribes audio via Azure Speech', function () {
    Http::fake([
        'test-region.api.cognitive.microsoft.com/*' => Http::response([
            'self' => 'https://test-region.api.cognitive.microsoft.com/transcriptions/123',
            'status' => 'Running',
            'id' => '123',
        ], 201),
    ]);

    $stt = new AzureSpeechService;
    $result = $stt->transcribe(
        ['https://example.com/audio.wav'],
        'Test Audio',
    );

    expect($result['status'])->toBe('Running')
        ->and($result['self'])->toContain('transcriptions/123');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'cognitive.microsoft.com'));
});

it('gets transcription status via Azure Speech', function () {
    Http::fake([
        'test-region.api.cognitive.microsoft.com/transcriptions/123' => Http::response([
            'status' => 'Succeeded',
            'links' => ['files' => 'https://test-region.api.cognitive.microsoft.com/transcriptions/123/files'],
        ], 200),
    ]);

    $stt = new AzureSpeechService;
    $result = $stt->getTranscriptionStatus(
        'https://test-region.api.cognitive.microsoft.com/transcriptions/123'
    );

    expect($result['status'])->toBe('Succeeded');
});

it('returns empty array on failed Azure transcription', function () {
    Http::fake([
        'test-region.api.cognitive.microsoft.com/*' => Http::response([], 500),
    ]);

    $stt = new AzureSpeechService;
    $result = $stt->transcribe(
        ['https://example.com/audio.wav'],
        'Test Audio',
    );

    expect($result)->toBe([]);
});

it('returns failed status on Azure transcription status error', function () {
    Http::fake([
        'test-region.api.cognitive.microsoft.com/*' => Http::response([], 500),
    ]);

    $stt = new AzureSpeechService;
    $result = $stt->getTranscriptionStatus(
        'https://test-region.api.cognitive.microsoft.com/transcriptions/123'
    );

    expect($result)->toBe(['status' => 'failed']);
});

it('returns empty array on failed Azure transcription files', function () {
    Http::fake([
        'test-region.api.cognitive.microsoft.com/*' => Http::response([], 500),
    ]);

    $stt = new AzureSpeechService;
    $result = $stt->getTranscriptionFiles(
        'https://test-region.api.cognitive.microsoft.com/transcriptions/123/files'
    );

    expect($result)->toBe([]);
});

it('returns empty array on failed Gemini content generation', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([], 500),
    ]);

    $ai = new GeminiAiService;
    $result = $ai->generateContent('System prompt', 'User prompt');

    expect($result)->toBe([]);
});

it('returns false for Africa\'s Talking blacklist check', function () {
    $gateway = new AfricasTalkingSmsGateway;
    $result = $gateway->checkBlacklist('ATXid_abc123');

    expect($result)->toBeFalse();
});
