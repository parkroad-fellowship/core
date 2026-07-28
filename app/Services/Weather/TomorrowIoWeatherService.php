<?php

namespace App\Services\Weather;

use App\Contracts\Services\WeatherServiceInterface;
use Illuminate\Support\Facades\Http;

class TomorrowIoWeatherService implements WeatherServiceInterface
{
    public function getForecast(float $latitude, float $longitude): array
    {
        $response = Http::get(config('prf.weather.api.url').'/weather/forecast', [
            'location' => "{$latitude}, {$longitude}",
            'apikey' => config('prf.weather.api.apiKey'),
            'units' => config('prf.weather.api.units'),
        ]);

        return $response->json();
    }
}
