<?php

namespace App\Jobs\Mission;

use App\Enums\PRFMorphType;
use App\Helpers\Utils;
use App\Models\Mission;
use App\Models\WeatherForecast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $mission->load('school');

        // Check if there are any existing weather forecasts for this mission
        if ($mission->weatherForecasts()->exists()) {
            return;
        }

        // Retrieve the weather forecast from the API
        $response = Http::get(config('prf.weather.api.url').'/weather/forecast', [
            'location' => "{$mission->school->latitude}, {$mission->school->longitude}",
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
            // Convert all dates to the same format for comparison
            $forecastDate = Carbon::parse($dailyEntry['time'])->startOfDay();
            $missionStartDate = $mission->start_date->copy()->startOfDay();
            $missionEndDate = $mission->end_date->copy()->startOfDay();

            if ($forecastDate->lt($missionStartDate) || $forecastDate->gt($missionEndDate)) {
                continue;
            }

            $cloudCoverUnit = config('prf.weather.metric_values.cloud_cover.unit');
            $dewPointUnit = config('prf.weather.metric_values.dew_point.unit');
            $humidityUnit = config('prf.weather.metric_values.humidity.unit');
            $precipitationProbabilityUnit = config('prf.weather.metric_values.precipitation_probability.unit');
            $rainAccumulationLweUnit = config('prf.weather.metric_values.rain_accumulation_lwe.unit');
            $rainAccumulationUnit = config('prf.weather.metric_values.rain_accumulation.unit');
            $rainIntensityUnit = config('prf.weather.metric_values.rain_intensity.unit');
            $temperatureApparentUnit = config('prf.weather.metric_values.temperature_apparent.unit');
            $temperatureUnit = config('prf.weather.metric_values.temperature.unit');
            $visibilityUnit = config('prf.weather.metric_values.visibility.unit');
            $windDirectionUnit = config('prf.weather.metric_values.wind_direction.unit');
            $windGustUnit = config('prf.weather.metric_values.wind_gust.unit');
            $windSpeedUnit = config('prf.weather.metric_values.wind_speed.unit');

            $dbEntries[] = [
                'ulid' => Utils::generateUlid(),
                'weather_forecastable_id' => $mission->id,
                'weather_forecastable_type' => PRFMorphType::MISSION->value,
                'forecast_date' => $dailyEntry['time'],
                'weather_code' => $weatherCode['key'],
                'weather_code_description' => $weatherCode['value'],
                'moon_rise_time' => $dailyEntry['moonriseTime'],
                'moon_set_time' => $dailyEntry['moonsetTime'],
                'sun_rise_time' => $dailyEntry['sunriseTime'],
                'sun_set_time' => $dailyEntry['sunsetTime'],

                'cloud_cover' => json_encode([
                    'avg' => $this->plugUnits($cloudCoverUnit, Arr::get($dailyEntry, 'cloudCoverAvg')),
                    'max' => $this->plugUnits($cloudCoverUnit, Arr::get($dailyEntry, 'cloudCoverMax')),
                    'min' => $this->plugUnits($cloudCoverUnit, Arr::get($dailyEntry, 'cloudCoverMin')),
                ]),

                'dew_point' => json_encode([
                    'avg' => $this->plugUnits($dewPointUnit, Arr::get($dailyEntry, 'dewPointAvg')),
                    'max' => $this->plugUnits($dewPointUnit, Arr::get($dailyEntry, 'dewPointMax')),
                    'min' => $this->plugUnits($dewPointUnit, Arr::get($dailyEntry, 'dewPointMin')),
                ]),

                'humidity' => json_encode([
                    'avg' => $this->plugUnits($humidityUnit, Arr::get($dailyEntry, 'humidityAvg')),
                    'max' => $this->plugUnits($humidityUnit, Arr::get($dailyEntry, 'humidityMax')),
                    'min' => $this->plugUnits($humidityUnit, Arr::get($dailyEntry, 'humidityMin')),
                ]),

                'precipitation_probability' => json_encode([
                    'avg' => $this->plugUnits($precipitationProbabilityUnit, Arr::get($dailyEntry, 'precipitationProbabilityAvg')),
                    'max' => $this->plugUnits($precipitationProbabilityUnit, Arr::get($dailyEntry, 'precipitationProbabilityMax')),
                    'min' => $this->plugUnits($precipitationProbabilityUnit, Arr::get($dailyEntry, 'precipitationProbabilityMin')),
                ]),

                'rain' => json_encode([
                    'accumulation_lwe_avg' => $this->plugUnits($rainAccumulationLweUnit, Arr::get($dailyEntry, 'rainAccumulationLweAvg')),
                    'accumulation_lwe_max' => $this->plugUnits($rainAccumulationLweUnit, Arr::get($dailyEntry, 'rainAccumulationLweMax')),
                    'accumulation_lwe_min' => $this->plugUnits($rainAccumulationLweUnit, Arr::get($dailyEntry, 'rainAccumulationLweMin')),
                    'accumulation_avg' => $this->plugUnits($rainAccumulationUnit, Arr::get($dailyEntry, 'rainAccumulationAvg')),
                    'accumulation_max' => $this->plugUnits($rainAccumulationUnit, Arr::get($dailyEntry, 'rainAccumulationMax')),
                    'accumulation_min' => $this->plugUnits($rainAccumulationUnit, Arr::get($dailyEntry, 'rainAccumulationMin')),
                    'accumulation_sum' => $this->plugUnits($rainAccumulationUnit, Arr::get($dailyEntry, 'rainAccumulationSum')),
                    'intensity_avg' => $this->plugUnits($rainIntensityUnit, Arr::get($dailyEntry, 'rainIntensityAvg')),
                    'intensity_max' => $this->plugUnits($rainIntensityUnit, Arr::get($dailyEntry, 'rainIntensityMax')),
                    'intensity_min' => $this->plugUnits($rainIntensityUnit, Arr::get($dailyEntry, 'rainIntensityMin')),
                ]),

                'temperature' => json_encode([
                    'apparent_avg' => $this->plugUnits($temperatureApparentUnit, Arr::get($dailyEntry, 'temperatureApparentAvg')),
                    'apparent_max' => $this->plugUnits($temperatureApparentUnit, Arr::get($dailyEntry, 'temperatureApparentMax')),
                    'apparent_min' => $this->plugUnits($temperatureApparentUnit, Arr::get($dailyEntry, 'temperatureApparentMin')),
                    'avg' => $this->plugUnits($temperatureUnit, Arr::get($dailyEntry, 'temperatureAvg')),
                    'max' => $this->plugUnits($temperatureUnit, Arr::get($dailyEntry, 'temperatureMax')),
                    'min' => $this->plugUnits($temperatureUnit, Arr::get($dailyEntry, 'temperatureMin')),
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
                    'avg' => $this->plugUnits($visibilityUnit, Arr::get($dailyEntry, 'visibilityAvg')),
                    'max' => $this->plugUnits($visibilityUnit, Arr::get($dailyEntry, 'visibilityMax')),
                    'min' => $this->plugUnits($visibilityUnit, Arr::get($dailyEntry, 'visibilityMin')),
                ]),

                'wind' => json_encode([
                    'direction_avg' => $this->plugUnits($windDirectionUnit, Arr::get($dailyEntry, 'windDirectionAvg')),
                    'gust_avg' => $this->plugUnits($windGustUnit, Arr::get($dailyEntry, 'windGustAvg')),
                    'gust_max' => $this->plugUnits($windGustUnit, Arr::get($dailyEntry, 'windGustMax')),
                    'gust_min' => $this->plugUnits($windGustUnit, Arr::get($dailyEntry, 'windGustMin')),
                    'speed_avg' => $this->plugUnits($windSpeedUnit, Arr::get($dailyEntry, 'windSpeedAvg')),
                    'speed_max' => $this->plugUnits($windSpeedUnit, Arr::get($dailyEntry, 'windSpeedMax')),
                    'speed_min' => $this->plugUnits($windSpeedUnit, Arr::get($dailyEntry, 'windSpeedMin')),
                ]),
                'forecast_data' => json_encode($dailyEntry),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        WeatherForecast::insert($dbEntries);
    }

    private static function plugUnits($unit, $value): string
    {
        return Str::of($value)
            ->whenNotEmpty(fn ($value) => $value)
            ->append(' ')
            ->append($unit)->__toString();
    }
}
