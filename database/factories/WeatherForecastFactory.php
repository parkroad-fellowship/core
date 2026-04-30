<?php

namespace Database\Factories;

use App\Enums\PRFMorphType;
use App\Models\Mission;
use App\Models\WeatherForecast;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends Factory<WeatherForecast>
 */
class WeatherForecastFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dailyEntry = $this->getSampleValues();
        $weatherCode = collect(config('prf.weather.codes'))->random();

        // Units
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

        $today = now();

        return [
            'weather_forecastable_id' => Mission::query()->inRandomOrder()->first()->getKey(),
            'weather_forecastable_type' => PRFMorphType::MISSION->value,
            'forecast_date' => $today->copy()->addDays($this->faker->numberBetween(1, 4)),
            'weather_code' => $weatherCode['key'],
            'weather_code_description' => $weatherCode['value'],
            'moon_rise_time' => $today->copy()->setHour(18)->setMinute(0)->setSecond(0),
            'moon_set_time' => $today->copy()->setHour(6)->setMinute(0)->setSecond(0),
            'sun_rise_time' => $today->copy()->setHour(6)->setMinute(0)->setSecond(0),
            'sun_set_time' => $today->copy()->setHour(18)->setMinute(0)->setSecond(0),

            'cloud_cover' => ([
                'avg' => $this->plugUnits($cloudCoverUnit, Arr::get($dailyEntry, 'cloudCoverAvg')),
                'max' => $this->plugUnits($cloudCoverUnit, Arr::get($dailyEntry, 'cloudCoverMax')),
                'min' => $this->plugUnits($cloudCoverUnit, Arr::get($dailyEntry, 'cloudCoverMin')),
            ]),

            'dew_point' => ([
                'avg' => $this->plugUnits($dewPointUnit, Arr::get($dailyEntry, 'dewPointAvg')),
                'max' => $this->plugUnits($dewPointUnit, Arr::get($dailyEntry, 'dewPointMax')),
                'min' => $this->plugUnits($dewPointUnit, Arr::get($dailyEntry, 'dewPointMin')),
            ]),

            'humidity' => ([
                'avg' => $this->plugUnits($humidityUnit, Arr::get($dailyEntry, 'humidityAvg')),
                'max' => $this->plugUnits($humidityUnit, Arr::get($dailyEntry, 'humidityMax')),
                'min' => $this->plugUnits($humidityUnit, Arr::get($dailyEntry, 'humidityMin')),
            ]),

            'precipitation_probability' => ([
                'avg' => $this->plugUnits($precipitationProbabilityUnit, Arr::get($dailyEntry, 'precipitationProbabilityAvg')),
                'max' => $this->plugUnits($precipitationProbabilityUnit, Arr::get($dailyEntry, 'precipitationProbabilityMax')),
                'min' => $this->plugUnits($precipitationProbabilityUnit, Arr::get($dailyEntry, 'precipitationProbabilityMin')),
            ]),

            'rain' => ([
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

            'temperature' => ([
                'apparent_avg' => $this->plugUnits($temperatureApparentUnit, Arr::get($dailyEntry, 'temperatureApparentAvg')),
                'apparent_max' => $this->plugUnits($temperatureApparentUnit, Arr::get($dailyEntry, 'temperatureApparentMax')),
                'apparent_min' => $this->plugUnits($temperatureApparentUnit, Arr::get($dailyEntry, 'temperatureApparentMin')),
                'avg' => $this->plugUnits($temperatureUnit, Arr::get($dailyEntry, 'temperatureAvg')),
                'max' => $this->plugUnits($temperatureUnit, Arr::get($dailyEntry, 'temperatureMax')),
                'min' => $this->plugUnits($temperatureUnit, Arr::get($dailyEntry, 'temperatureMin')),
            ]),

            'uv' => ([
                'health_concern_avg' => Arr::get($dailyEntry, 'uvHealthConcernAvg'),
                'health_concern_max' => Arr::get($dailyEntry, 'uvHealthConcernMax'),
                'health_concern_min' => Arr::get($dailyEntry, 'uvHealthConcernMin'),
                'index_avg' => Arr::get($dailyEntry, 'uvIndexAvg'),
                'index_max' => Arr::get($dailyEntry, 'uvIndexMax'),
                'index_min' => Arr::get($dailyEntry, 'uvIndexMin'),
            ]),

            'visibility' => ([
                'avg' => $this->plugUnits($visibilityUnit, Arr::get($dailyEntry, 'visibilityAvg')),
                'max' => $this->plugUnits($visibilityUnit, Arr::get($dailyEntry, 'visibilityMax')),
                'min' => $this->plugUnits($visibilityUnit, Arr::get($dailyEntry, 'visibilityMin')),
            ]),

            'wind' => ([
                'direction_avg' => $this->plugUnits($windDirectionUnit, Arr::get($dailyEntry, 'windDirectionAvg')),
                'gust_avg' => $this->plugUnits($windGustUnit, Arr::get($dailyEntry, 'windGustAvg')),
                'gust_max' => $this->plugUnits($windGustUnit, Arr::get($dailyEntry, 'windGustMax')),
                'gust_min' => $this->plugUnits($windGustUnit, Arr::get($dailyEntry, 'windGustMin')),
                'speed_avg' => $this->plugUnits($windSpeedUnit, Arr::get($dailyEntry, 'windSpeedAvg')),
                'speed_max' => $this->plugUnits($windSpeedUnit, Arr::get($dailyEntry, 'windSpeedMax')),
                'speed_min' => $this->plugUnits($windSpeedUnit, Arr::get($dailyEntry, 'windSpeedMin')),
            ]),
            'forecast_data' => ($dailyEntry),
        ];
    }

    private static function plugUnits($unit, $value): string
    {
        if ($value === null) {
            return '';
        }

        return Str::of($value)
            ->append(' ')
            ->append($unit)->__toString();
    }

    private function getSampleValues(): array
    {
        return [
            'cloudBaseAvg' => 1.38,
            'cloudBaseMax' => 2.8,
            'cloudBaseMin' => 0,
            'cloudCeilingAvg' => 1.3,
            'cloudCeilingMax' => 2.96,
            'cloudCeilingMin' => 0,
            'cloudCoverAvg' => 66.39,
            'cloudCoverMax' => 100,
            'cloudCoverMin' => 3,
            'dewPointAvg' => 12.73,
            'dewPointMax' => 14.44,
            'dewPointMin' => 10.88,
            'evapotranspirationAvg' => 0.177,
            'evapotranspirationMax' => 0.613,
            'evapotranspirationMin' => 0,
            'evapotranspirationSum' => 4.236,
            'freezingRainIntensityAvg' => 0,
            'freezingRainIntensityMax' => 0,
            'freezingRainIntensityMin' => 0,
            'hailProbabilityAvg' => 39.9,
            'hailProbabilityMax' => 98.6,
            'hailProbabilityMin' => 3,
            'hailSizeAvg' => 5.31,
            'hailSizeMax' => 9.42,
            'hailSizeMin' => 0.04,
            'humidityAvg' => 68.67,
            'humidityMax' => 94,
            'humidityMin' => 41,
            'iceAccumulationAvg' => 0,
            'iceAccumulationLweAvg' => 0,
            'iceAccumulationLweMax' => 0,
            'iceAccumulationLweMin' => 0,
            'iceAccumulationLweSum' => 0,
            'iceAccumulationMax' => 0,
            'iceAccumulationMin' => 0,
            'iceAccumulationSum' => 0,
            'moonriseTime' => '2025-01-12T14 =>41 =>53Z',
            'moonsetTime' => '2025-01-12T02 =>07 =>25Z',
            'precipitationProbabilityAvg' => 2.7,
            'precipitationProbabilityMax' => 10,
            'precipitationProbabilityMin' => 0,
            'pressureSurfaceLevelAvg' => 811.53,
            'pressureSurfaceLevelMax' => 813.63,
            'pressureSurfaceLevelMin' => 808.54,
            'rainAccumulationAvg' => 0.19,
            'rainAccumulationLweAvg' => 0.18,
            'rainAccumulationLweMax' => 2.01,
            'rainAccumulationLweMin' => 0,
            'rainAccumulationMax' => 1.94,
            'rainAccumulationMin' => 0,
            'rainAccumulationSum' => 4.59,
            'rainIntensityAvg' => 0.18,
            'rainIntensityMax' => 2.01,
            'rainIntensityMin' => 0,
            'sleetAccumulationAvg' => 0,
            'sleetAccumulationLweAvg' => 0,
            'sleetAccumulationLweMax' => 0,
            'sleetAccumulationLweMin' => 0,
            'sleetAccumulationLweSum' => 0,
            'sleetAccumulationMax' => 0,
            'sleetAccumulationMin' => 0,
            'sleetIntensityAvg' => 0,
            'sleetIntensityMax' => 0,
            'sleetIntensityMin' => 0,
            'snowAccumulationAvg' => 0,
            'snowAccumulationLweAvg' => 0,
            'snowAccumulationLweMax' => 0,
            'snowAccumulationLweMin' => 0,
            'snowAccumulationLweSum' => 0,
            'snowAccumulationMax' => 0,
            'snowAccumulationMin' => 0,
            'snowAccumulationSum' => 0,
            'snowIntensityAvg' => 0,
            'snowIntensityMax' => 0,
            'snowIntensityMin' => 0,
            'sunriseTime' => '2025-01-12T03 =>36 =>00Z',
            'sunsetTime' => '2025-01-12T15 =>46 =>00Z',
            'temperatureApparentAvg' => 19.2,
            'temperatureApparentMax' => 25.06,
            'temperatureApparentMin' => 13.25,
            'temperatureAvg' => 19.2,
            'temperatureMax' => 25.06,
            'temperatureMin' => 13.25,
            'uvHealthConcernAvg' => 1,
            'uvHealthConcernMax' => 4,
            'uvHealthConcernMin' => 0,
            'uvIndexAvg' => 2,
            'uvIndexMax' => 10,
            'uvIndexMin' => 0,
            'visibilityAvg' => 13.44,
            'visibilityMax' => 16,
            'visibilityMin' => 8.33,
            'weatherCodeMax' => 1001,
            'weatherCodeMin' => 1001,
            'windDirectionAvg' => 41.18,
            'windGustAvg' => 6.75,
            'windGustMax' => 9.5,
            'windGustMin' => 3.19,
            'windSpeedAvg' => 3.86,
            'windSpeedMax' => 5.25,
            'windSpeedMin' => 2.69,
        ];
    }
}
