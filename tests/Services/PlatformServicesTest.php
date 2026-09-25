<?php

use App\Contracts\Services\MapsServiceInterface;
use App\Contracts\Services\NLPServiceInterface;
use App\Contracts\Services\SpeechToTextServiceInterface;
use App\Contracts\Services\WeatherServiceInterface;
use App\Jobs\NLP\EmbedContentJob;
use App\Services\Maps\GoogleMapsService;
use App\Services\NLP\DefaultNLPService;
use App\Services\SpeechToText\AzureSpeechService;
use App\Services\Weather\TomorrowIOWeatherService;
use Illuminate\Support\Facades\Http;

/*
 | Platform services are owned by PRF and configured through config/prf/*.php (env),
 | unlike tenant-owned integrations (see tests/Feature/Integrations).
 */

beforeEach(function () {
    config([
        'prf.nlp.api_key' => 'nlp-test-key',
        'prf.nlp.base_url' => 'https://nlp.test.com',
        'prf.nlp.default_bot' => 'TestBot',
        'prf.weather.api.url' => 'https://api.tomorrow.io/v4',
        'prf.weather.api.apiKey' => 'weather-test-key',
        'prf.weather.api.units' => 'metric',
        'prf.app.google_maps.api_key' => 'maps-test-key',
        'prf.app.azure_speech.subscription_key' => 'azure-test-key',
        'prf.app.azure_speech.region' => 'test-region',
    ]);
});

it('binds each platform service interface to its implementation', function () {
    expect(app(NLPServiceInterface::class))
        ->toBeInstanceOf(DefaultNLPService::class)
        ->and(app(WeatherServiceInterface::class))
        ->toBeInstanceOf(TomorrowIOWeatherService::class)
        ->and(app(MapsServiceInterface::class))
        ->toBeInstanceOf(GoogleMapsService::class)
        ->and(app(SpeechToTextServiceInterface::class))
        ->toBeInstanceOf(AzureSpeechService::class);
});

it('embeds content via the NLP service', function () {
    Http::fake(['nlp.test.com/embedding/init' => Http::response(['status' => 'ok'])]);

    new DefaultNLPService()->embedContent(['test document 1', 'test document 2']);

    Http::assertSent(
        fn($request) => (
            str_contains($request->url(), 'embedding/init') && str_contains($request->body(), 'test document 1')
        ),
    );
});

it('passes documents to the NLP service from EmbedContentJob', function () {
    Http::fake(['nlp.test.com/embedding/init' => Http::response(['status' => 'ok'])]);

    new EmbedContentJob(['doc1', 'doc2'])->handle(app(NLPServiceInterface::class));

    Http::assertSent(fn($request) => str_contains($request->body(), 'doc2'));
});

it('gets a weather forecast from Tomorrow.io', function () {
    Http::fake(['api.tomorrow.io/*' => Http::response([
        'timelines' => ['daily' => [['time' => '2026-07-23T00:00:00Z', 'values' => ['temperatureAvg' => 25]]]],
    ])]);

    $result = new TomorrowIOWeatherService()->getForecast(-1.29, 36.77);

    expect($result['timelines']['daily'])->toHaveCount(1);
    Http::assertSent(fn($request) => str_contains($request->url(), 'weather/forecast'));
});

it('computes a route via Google Maps', function () {
    Http::fake(['routes.googleapis.com/*' => Http::response([
        'routes' => [[
            'localizedValues' => ['distance' => ['text' => '10 km'], 'staticDuration' => ['text' => '15 mins']],
        ]],
    ])]);

    $result = new GoogleMapsService()->computeRoute(['latitude' => -1.29, 'longitude' => 36.77], [
        'latitude' => -1.30,
        'longitude' => 36.78,
    ]);

    expect($result['routes'][0]['localizedValues']['distance']['text'])->toBe('10 km');
});

describe('Azure speech', function () {
    it('submits a transcription', function () {
        Http::fake(['test-region.api.cognitive.microsoft.com/*' => Http::response([
            'self' => 'https://test-region.api.cognitive.microsoft.com/transcriptions/123',
            'status' => 'Running',
        ], 201)]);

        $result = new AzureSpeechService()->transcribe(['https://example.com/audio.wav'], 'Test Audio');

        expect($result['status'])->toBe('Running');
    });

    it('reads the transcription status', function () {
        Http::fake(['test-region.api.cognitive.microsoft.com/transcriptions/123' => Http::response([
            'status' => 'Succeeded',
        ])]);

        $result = new AzureSpeechService()->getTranscriptionStatus(
            'https://test-region.api.cognitive.microsoft.com/transcriptions/123',
        );

        expect($result['status'])->toBe('Succeeded');
    });

    it('returns empty results when Azure fails', function () {
        Http::fake(['test-region.api.cognitive.microsoft.com/*' => Http::response([], 500)]);

        $stt = new AzureSpeechService();

        expect($stt->transcribe(['https://example.com/audio.wav'], 'Test Audio'))
            ->toBe([])
            ->and($stt->getTranscriptionStatus('https://test-region.api.cognitive.microsoft.com/transcriptions/123'))
            ->toBe(['status' => 'failed'])
            ->and($stt->getTranscriptionFiles(
                'https://test-region.api.cognitive.microsoft.com/transcriptions/123/files',
            ))
            ->toBe([]);
    });
});
