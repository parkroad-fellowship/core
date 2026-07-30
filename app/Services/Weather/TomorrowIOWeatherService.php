<?php

namespace App\Services\Weather;

use App\Contracts\Services\WeatherServiceInterface;
use Illuminate\Support\Facades\Http;

class TomorrowIOWeatherService implements WeatherServiceInterface
{
    public function getForecast(float $latitude, float $longitude): array
    {
        $baseUrl = rtrim((string) config('prf.weather.api.url', 'https://api.tomorrow.io/v4'), '/');

        $response = Http::get($baseUrl.'/weather/forecast', [
            'location' => "{$latitude}, {$longitude}",
            'apikey' => config('prf.weather.api.apiKey'),
            'units' => config('prf.weather.api.units'),
        ]);

        return $response->json();
    }
}
