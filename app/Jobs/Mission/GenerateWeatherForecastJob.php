<?php

namespace App\Jobs\Mission;

use App\Helpers\Utils;
use App\Models\Mission;
use App\Models\WeatherForecast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class GenerateWeatherForecastJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Mission $mission,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mission = $this->mission;

        // Check if there are any existing weather forecasts for this mission
        if ($mission->weatherForecasts()->exists()) {
            return;
        }

        // Retrieve the weather forecast from the API
        $response = Http::get(config('prf.weather.api.url') . '/weather/forecast', [
            'location' => $mission->location,
            'apikey' => config('prf.weather.api.apiKey'),
            'units' => config('prf.weather.api.units'),
        ]);


        $dailyEntries = collect($response->json('timelines.daily', []))->map(function ($dailyEntry) {
            return [
                'time' => $dailyEntry['time'],
                ...Arr::get($dailyEntry, 'values', []),
            ];
        });
        $weatherCodes = collect(config('prf.weather.codes'));

        $now = now();

        $dbEntries = [];

        // Save the weather forecast to the database
        foreach ($dailyEntries as $dailyEntry) {

            $weatherCode = $weatherCodes->firstWhere('key', $dailyEntry['weatherCodeMax']);

            Log::info('Dates: ', [
                'start_date' => $mission->start_date,
                'end_date' => $mission->end_date,
                'forecast_date' => $dailyEntry['time'],
            ]);
            // If the time for this entry is outside the mission date range, skip
            if ($mission->start_date->gt($dailyEntry['time']) || $mission->end_date->lt($dailyEntry['time'])) {
                continue;
            }

            $dbEntries[] =  [
                'ulid' => Utils::generateUlid(),
                'mission_id' => $mission->id,
                'forecast_date' => $dailyEntry['time'],
                'weather_code' =>  $weatherCode['key'],
                'weather_code_description' => $weatherCode['value'],
                'moon_rise_time' => $dailyEntry['moonriseTime'],
                'moon_set_time' => $dailyEntry['moonsetTime'],
                'sun_rise_time' => $dailyEntry['sunriseTime'],
                'sun_set_time' => $dailyEntry['sunsetTime'],

                'cloud_cover' => json_encode([
                    'avg' => Arr::get($dailyEntry, 'cloudCoverAvg'),
                    'max' => Arr::get($dailyEntry, 'cloudCoverMax'),
                    'min' => Arr::get($dailyEntry, 'cloudCoverMin'),
                ]),

                'dew_point' => json_encode([
                    'avg' => Arr::get($dailyEntry, 'dewPointAvg'),
                    'max' => Arr::get($dailyEntry, 'dewPointMax'),
                    'min' => Arr::get($dailyEntry, 'dewPointMin'),
                ]),

                'humidity' => json_encode([
                    'avg' => Arr::get($dailyEntry, 'humidityAvg'),
                    'max' => Arr::get($dailyEntry, 'humidityMax'),
                    'min' => Arr::get($dailyEntry, 'humidityMin'),
                ]),

                'precipitation_probability' => json_encode([
                    'avg' => Arr::get($dailyEntry, 'precipitationProbabilityAvg'),
                    'max' => Arr::get($dailyEntry, 'precipitationProbabilityMax'),
                    'min' => Arr::get($dailyEntry, 'precipitationProbabilityMin'),
                ]),

                'rain' => json_encode([
                    'accumulation_lwe_avg' => Arr::get($dailyEntry, 'rainAccumulationLweAvg'),
                    'accumulation_lwe_max' => Arr::get($dailyEntry, 'rainAccumulationLweMax'),
                    'accumulation_lwe_min' => Arr::get($dailyEntry, 'rainAccumulationLweMin'),
                    'accumulation_avg' => Arr::get($dailyEntry, 'rainAccumulationAvg'),
                    'accumulation_max' =>   Arr::get($dailyEntry, 'rainAccumulationMax'),
                    'accumulation_min' => Arr::get($dailyEntry, 'rainAccumulationMin'),
                    'accumulation_sum' => Arr::get($dailyEntry, 'rainAccumulationSum'),
                    'intensity_avg' => Arr::get($dailyEntry, 'rainIntensityAvg'),
                    'intensity_max' => Arr::get($dailyEntry, 'rainIntensityMax'),
                    'intensity_min' => Arr::get($dailyEntry, 'rainIntensityMin'),
                ]),

                'temperature' => json_encode([
                    'apparent_avg' => Arr::get($dailyEntry, 'temperatureApparentAvg'),
                    'apparent_max' => Arr::get($dailyEntry, 'temperatureApparentMax'),
                    'apparent_min' => Arr::get($dailyEntry, 'temperatureApparentMin'),
                    'avg' => Arr::get($dailyEntry, 'temperatureAvg'),
                    'max' => Arr::get($dailyEntry, 'temperatureMax'),
                    'min' => Arr::get($dailyEntry, 'temperatureMin'),
                ]),

                'uv' => json_encode([
                    'health_concern_avg' => Arr::get($dailyEntry, 'uvHealthConcernAvg'),
                    'health_concern_max' => Arr::get($dailyEntry, 'uvHealthConcernMax'),
                    'health_concern_min' => Arr::get($dailyEntry, 'uvHealthConcernMin'),
                    'index_avg' => Arr::get($dailyEntry, 'uvIndexAvg'),
                    'index_max' => Arr::get($dailyEntry, 'uvIndexMax'),
                    'index_min' => Arr::get($dailyEntry, 'uvIndexMin'),
                ]),

                'visibility' => json_encode([
                    'avg' => Arr::get($dailyEntry, 'visibilityAvg'),
                    'max' => Arr::get($dailyEntry, 'visibilityMax'),
                    'min' => Arr::get($dailyEntry, 'visibilityMin'),
                ]),

                'wind' => json_encode([
                    'direction_avg' => Arr::get($dailyEntry, 'windDirectionAvg'),
                    'gust_avg' => Arr::get($dailyEntry, 'windGustAvg'),
                    'gust_max' => Arr::get($dailyEntry, 'windGustMax'),
                    'gust_min' => Arr::get($dailyEntry, 'windGustMin'),
                    'speed_avg' => Arr::get($dailyEntry, 'windSpeedAvg'),
                    'speed_max' => Arr::get($dailyEntry, 'windSpeedMax'),
                    'speed_min' => Arr::get($dailyEntry, 'windSpeedMin'),
                ]),
                'forecast_data' => json_encode($dailyEntry),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        WeatherForecast::insert($dbEntries);
    }
}
