<?php

namespace App\Contracts\Services;

interface WeatherServiceInterface
{
    /**
     * Get a weather forecast for a location.
     *
     * @return array{timelines?: array, location?: array}
     */
    public function getForecast(float $latitude, float $longitude): array;
}
