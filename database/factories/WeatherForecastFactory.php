<?php

namespace Database\Factories;

use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WeatherForecast>
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
        $sampleValues = $this->getSampleValues();
        $weatherCode = collect(config('prf.weather_codes'))->random();

        $today = now(); 

        return [
            'mission_id' => Mission::query()->inRandomOrder()->first()->getKey(),
            'forecast_date' => $today->copy()->addDays($this->faker->numberBetween(1, 4)),
            'weather_code' => $weatherCode['key'],
            'weather_code_description' => $weatherCode['value'],
            'moon_rise_time' => $today->copy()->setHour(18)->setMinute(0)->setSecond(0),
            'moon_set_time' => $today->copy()->setHour(6)->setMinute(0)->setSecond(0),
            'sun_rise_time' => $today->copy()->setHour(6)->setMinute(0)->setSecond(0),
            'sun_set_time' => $today->copy()->setHour(18)->setMinute(0)->setSecond(0),

            'cloud_cover' => [
                'avg' => $sampleValues->firstWhere('key', 'cloudCoverAvg')['value'],
                'max' => $sampleValues->firstWhere('key', 'cloudCoverMax')['value'],
                'min' => $sampleValues->firstWhere('key', 'cloudCoverMin')['value'],
            ],

            'dew_point' => [
                'avg' => $sampleValues->firstWhere('key', 'dewPointAvg')['value'],
                'max' => $sampleValues->firstWhere('key', 'dewPointMax')['value'],
                'min' => $sampleValues->firstWhere('key', 'dewPointMin')['value'],
            ],

            'humidity' => [
                'avg' => $sampleValues->firstWhere('key', 'humidityAvg')['value'],
                'max' => $sampleValues->firstWhere('key', 'humidityMax')['value'],
                'min' => $sampleValues->firstWhere('key', 'humidityMin')['value'],
            ],

            'precipitation_probability' => [
                'avg' => $sampleValues->firstWhere('key', 'precipitationProbabilityAvg')['value'],
                'max' => $sampleValues->firstWhere('key', 'precipitationProbabilityMax')['value'],
                'min' => $sampleValues->firstWhere('key', 'precipitationProbabilityMin')['value'],
            ],

            'rain' => [
                'accumulation_lwe_avg' => $sampleValues->firstWhere('key', 'rainAccumulationLweAvg')['value'],
                'accumulation_lwe_max' => $sampleValues->firstWhere('key', 'rainAccumulationLweMax')['value'],
                'accumulation_lwe_min' => $sampleValues->firstWhere('key', 'rainAccumulationLweMin')['value'],
                'accumulation_avg' => $sampleValues->firstWhere('key', 'rainAccumulationAvg')['value'],
                'accumulation_max' => $sampleValues->firstWhere('key', 'rainAccumulationMax')['value'],
                'accumulation_min' => $sampleValues->firstWhere('key', 'rainAccumulationMin')['value'],
                'accumulation_sum' => $sampleValues->firstWhere('key', 'rainAccumulationSum')['value'],
                'intensity_avg' => $sampleValues->firstWhere('key', 'rainIntensityAvg')['value'],
                'intensity_max' => $sampleValues->firstWhere('key', 'rainIntensityMax')['value'],
                'intensity_min' => $sampleValues->firstWhere('key', 'rainIntensityMin')['value'],
            ],

            'temperature' => [
                'apparent_avg' => $sampleValues->firstWhere('key', 'temperatureApparentAvg')['value'],
                'apparent_max' => $sampleValues->firstWhere('key', 'temperatureApparentMax')['value'],
                'apparent_min' => $sampleValues->firstWhere('key', 'temperatureApparentMin')['value'],
                'avg' => $sampleValues->firstWhere('key', 'temperatureAvg')['value'],
                'max' => $sampleValues->firstWhere('key', 'temperatureMax')['value'],
                'min' => $sampleValues->firstWhere('key', 'temperatureMin')['value'],
            ],

            'uv' => [
                'health_concern_avg' => $sampleValues->firstWhere('key', 'uvHealthConcernAvg')['value'],
                'health_concern_max' => $sampleValues->firstWhere('key', 'uvHealthConcernMax')['value'],
                'health_concern_min' => $sampleValues->firstWhere('key', 'uvIndexMin')['value'],
                'index_avg' => $sampleValues->firstWhere('key', 'uvIndexAvg')['value'],
                'index_max' => $sampleValues->firstWhere('key', 'uvIndexMax')['value'],
                'index_min' => $sampleValues->firstWhere('key', 'uvIndexMin')['value'],
            ],

            'visibility' => [
                'avg' => $sampleValues->firstWhere('key', 'visibilityAvg')['value'],
                'max' => $sampleValues->firstWhere('key', 'visibilityMax')['value'],
                'min' => $sampleValues->firstWhere('key', 'visibilityMin')['value'],
            ],

            'wind' => [
                'direction_avg' => $sampleValues->firstWhere('key', 'windDirectionAvg')['value'],
                'gust_avg' => $sampleValues->firstWhere('key', 'windGustAvg')['value'],
                'gust_max' => $sampleValues->firstWhere('key', 'windGustMax')['value'],
                'gust_min' => $sampleValues->firstWhere('key', 'windGustMin')['value'],
                'speed_avg' => $sampleValues->firstWhere('key', 'windSpeedAvg')['value'],
                'speed_max' => $sampleValues->firstWhere('key', 'windSpeedMax')['value'],
                'speed_min' => $sampleValues->firstWhere('key', 'windSpeedMin')['value'],
            ],
        ];
    }


    private function getSampleValues()
    {
        return collect([
            [
                "key" => "cloudBaseAvg",
                "value" => 0.97
            ],
            [
                "key" => "cloudBaseMax",
                "value" => 3.12
            ],
            [
                "key" => "cloudBaseMin",
                "value" => 0
            ],
            [
                "key" => "cloudCeilingAvg",
                "value" => 1.1
            ],
            [
                "key" => "cloudCeilingMax",
                "value" => 11.35
            ],
            [
                "key" => "cloudCeilingMin",
                "value" => 0
            ],
            [
                "key" => "cloudCoverAvg",
                "value" => 43.75
            ],
            [
                "key" => "cloudCoverMax",
                "value" => 100
            ],
            [
                "key" => "cloudCoverMin",
                "value" => 2
            ],
            [
                "key" => "dewPointAvg",
                "value" => 11.82
            ],
            [
                "key" => "dewPointMax",
                "value" => 14
            ],
            [
                "key" => "dewPointMin",
                "value" => 8.19
            ],
            [
                "key" => "evapotranspirationAvg",
                "value" => 0.202
            ],
            [
                "key" => "evapotranspirationMax",
                "value" => 0.679
            ],
            [
                "key" => "evapotranspirationMin",
                "value" => 0
            ],
            [
                "key" => "evapotranspirationSum",
                "value" => 4.85
            ],
            [
                "key" => "freezingRainIntensityAvg",
                "value" => 0
            ],
            [
                "key" => "freezingRainIntensityMax",
                "value" => 0
            ],
            [
                "key" => "freezingRainIntensityMin",
                "value" => 0
            ],
            [
                "key" => "hailProbabilityAvg",
                "value" => 55.9
            ],
            [
                "key" => "hailProbabilityMax",
                "value" => 89.3
            ],
            [
                "key" => "hailProbabilityMin",
                "value" => 8.5
            ],
            [
                "key" => "hailSizeAvg",
                "value" => 4.5
            ],
            [
                "key" => "hailSizeMax",
                "value" => 9.7
            ],
            [
                "key" => "hailSizeMin",
                "value" => 0.36
            ],
            [
                "key" => "humidityAvg",
                "value" => 67.08
            ],
            [
                "key" => "humidityMax",
                "value" => 97
            ],
            [
                "key" => "humidityMin",
                "value" => 34
            ],
            [
                "key" => "iceAccumulationAvg",
                "value" => 0
            ],
            [
                "key" => "iceAccumulationLweAvg",
                "value" => 0
            ],
            [
                "key" => "iceAccumulationLweMax",
                "value" => 0
            ],
            [
                "key" => "iceAccumulationLweMin",
                "value" => 0
            ],
            [
                "key" => "iceAccumulationLweSum",
                "value" => 0
            ],
            [
                "key" => "iceAccumulationMax",
                "value" => 0
            ],
            [
                "key" => "iceAccumulationMin",
                "value" => 0
            ],
            [
                "key" => "iceAccumulationSum",
                "value" => 0
            ],
            [
                "key" => "precipitationProbabilityAvg",
                "value" => 0.4
            ],
            [
                "key" => "precipitationProbabilityMax",
                "value" => 5
            ],
            [
                "key" => "precipitationProbabilityMin",
                "value" => 0
            ],
            [
                "key" => "pressureSurfaceLevelAvg",
                "value" => 812.56
            ],
            [
                "key" => "pressureSurfaceLevelMax",
                "value" => 814.38
            ],
            [
                "key" => "pressureSurfaceLevelMin",
                "value" => 811
            ],
            [
                "key" => "rainAccumulationAvg",
                "value" => 0
            ],
            [
                "key" => "rainAccumulationLweAvg",
                "value" => 0
            ],
            [
                "key" => "rainAccumulationLweMax",
                "value" => 0.06
            ],
            [
                "key" => "rainAccumulationLweMin",
                "value" => 0
            ],
            [
                "key" => "rainAccumulationMax",
                "value" => 0
            ],
            [
                "key" => "rainAccumulationMin",
                "value" => 0
            ],
            [
                "key" => "rainAccumulationSum",
                "value" => 0
            ],
            [
                "key" => "rainIntensityAvg",
                "value" => 0
            ],
            [
                "key" => "rainIntensityMax",
                "value" => 0.06
            ],
            [
                "key" => "rainIntensityMin",
                "value" => 0
            ],
            [
                "key" => "sleetAccumulationAvg",
                "value" => 0
            ],
            [
                "key" => "sleetAccumulationLweAvg",
                "value" => 0
            ],
            [
                "key" => "sleetAccumulationLweMax",
                "value" => 0
            ],
            [
                "key" => "sleetAccumulationLweMin",
                "value" => 0
            ],
            [
                "key" => "sleetAccumulationLweSum",
                "value" => 0
            ],
            [
                "key" => "sleetAccumulationMax",
                "value" => 0
            ],
            [
                "key" => "sleetAccumulationMin",
                "value" => 0
            ],
            [
                "key" => "sleetIntensityAvg",
                "value" => 0
            ],
            [
                "key" => "sleetIntensityMax",
                "value" => 0
            ],
            [
                "key" => "sleetIntensityMin",
                "value" => 0
            ],
            [
                "key" => "snowAccumulationAvg",
                "value" => 0
            ],
            [
                "key" => "snowAccumulationLweAvg",
                "value" => 0
            ],
            [
                "key" => "snowAccumulationLweMax",
                "value" => 0
            ],
            [
                "key" => "snowAccumulationLweMin",
                "value" => 0
            ],
            [
                "key" => "snowAccumulationLweSum",
                "value" => 0
            ],
            [
                "key" => "snowAccumulationMax",
                "value" => 0
            ],
            [
                "key" => "snowAccumulationMin",
                "value" => 0
            ],
            [
                "key" => "snowAccumulationSum",
                "value" => 0
            ],
            [
                "key" => "snowIntensityAvg",
                "value" => 0
            ],
            [
                "key" => "snowIntensityMax",
                "value" => 0
            ],
            [
                "key" => "snowIntensityMin",
                "value" => 0
            ],
            [
                "key" => "temperatureApparentAvg",
                "value" => 18.82
            ],
            [
                "key" => "temperatureApparentMax",
                "value" => 25
            ],
            [
                "key" => "temperatureApparentMin",
                "value" => 13.5
            ],
            [
                "key" => "temperatureAvg",
                "value" => 18.82
            ],
            [
                "key" => "temperatureMax",
                "value" => 25
            ],
            [
                "key" => "temperatureMin",
                "value" => 13.5
            ],
            [
                "key" => "uvHealthConcernAvg",
                "value" => 1
            ],
            [
                "key" => "uvHealthConcernMax",
                "value" => 3
            ],
            [
                "key" => "uvHealthConcernMin",
                "value" => 0
            ],
            [
                "key" => "uvIndexAvg",
                "value" => 2
            ],
            [
                "key" => "uvIndexMax",
                "value" => 10
            ],
            [
                "key" => "uvIndexMin",
                "value" => 0
            ],
            [
                "key" => "visibilityAvg",
                "value" => 13.21
            ],
            [
                "key" => "visibilityMax",
                "value" => 16
            ],
            [
                "key" => "visibilityMin",
                "value" => 8.12
            ],
            [
                "key" => "weatherCodeMax",
                "value" => 1100
            ],
            [
                "key" => "weatherCodeMin",
                "value" => 1100
            ],
            [
                "key" => "windDirectionAvg",
                "value" => 47.33
            ],
            [
                "key" => "windGustAvg",
                "value" => 7.3
            ],
            [
                "key" => "windGustMax",
                "value" => 9.38
            ],
            [
                "key" => "windGustMin",
                "value" => 5.75
            ],
            [
                "key" => "windSpeedAvg",
                "value" => 4.17
            ],
            [
                "key" => "windSpeedMax",
                "value" => 5.5
            ],
            [
                "key" => "windSpeedMin",
                "value" => 2.69
            ]
        ]);
    }
}
