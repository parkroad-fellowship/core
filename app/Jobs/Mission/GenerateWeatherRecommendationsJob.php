<?php

namespace App\Jobs\Mission;

use App\Models\Mission;
use App\Models\WeatherForecast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateWeatherRecommendationsJob implements ShouldQueue
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

        $forecasts = WeatherForecast::query()
            ->where('mission_id', $mission->id)
            ->get();

        if (! $forecasts->count()) {
            Log::error('Weather forecast not found for mission', ['mission_id' => $mission->id]);

            return;
        }

        $systemPrompt = <<<'EOT'
            You are an AI assistant designed to provide recommendations for a high school-focused gospel missions group. You will receive weather forecast data in JSON format for several upcoming days. Based on this data, and considering the group's mission policy (provided below), generate a JSON output with dressing recommendations and weather-specific activity suggestions. The activities should focus on gospel-related opportunities. Make sure the recommended dressing aligns with the following mission policy:

            Mission Policy:

            H. DRESS CODE POLICY				
            General Guidelines
            Missioners are expected to dress in a manner that is respectful of the local culture and suitable for the mission environment.
            Clothing should be clean, modest, and practical, allowing for mobility and comfort.
            Personal hygiene should be maintained to ensure a pleasant environment for all.
            Unless otherwise advised by the mission leader for specific missions:
            Mission Attire:
            General Mission Work:
            One should be in lightweight, breathable clothing that is modest and covers shoulders and knees.
            Men: T-shirts, shirts, long pants and closed-toe shoes.	
            Women: Dresses or skirts that cover the knees, blouses that cover the shoulders, and closed-toe shoes or dress sandals.
            Cultural Sensitivity:
            Be mindful of local customs and traditions regarding attire. When in doubt, seek guidance from local contacts or mission leaders.
            Avoid clothing with logos, graphics, or slogans that could be considered offensive or inappropriate.								
            Practical Considerations:
            Pack versatile clothing that can be layered for varying weather conditions.
            Bring appropriate gear for specific mission tasks (e.g., gloves, work boots, rain gear).
            Accessories and Grooming:
            Tattoos and piercings should be covered or removed if deemed inappropriate for the mission environment.
            Hair should be clean, neat, and styled in a practical manner for the mission activities.
            Enforcement:
            Mission leaders are responsible for ensuring compliance with the dress code.
            Participants in violation of the dress code may be asked to change attire or may be restricted from certain activities until compliant.
            Repeated violations may result in removal from the mission.
            Policy Review:
            This policy will be reviewed annually and may be updated as needed.
            Participants will be informed of any changes to the dress code policy prior to mission deployment.

            Output Format:

            The output must be in JSON format with a top-level key called "recommendations," containing a JSON array of objects. Each object in the array represents a day, with the following keys:
            *   `date`: (string - YYYY-MM-DD)
            *   `weather_description`: (string)
            *   `temperature_range`: (string, e.g., "13°C to 25.44°C")
            *   `precipitation_probability`: (string, e.g., "Low (0-5%)")
            *   `dressing`: (JSON array of strings with specific clothing advice)
            *  `activities`: (JSON array of strings with specific, weather-related gospel outreach activities)

            Here is an example JSON input

            ```json
            {
            "weather_forecasts": [
                {
                "entity": "weather-forecast",
                "forecast_date": "2025-01-12 03:00:00",
                "weather_code": 1100,
                "weather_code_description": "Mostly Clear",
                "moon_rise_time": "2025-01-12 14:41:53",
                "moon_set_time": "2025-01-12 02:07:25",
                "sun_rise_time": "2025-01-12 03:36:00",
                "sun_set_time": "2025-01-12 15:46:00",
                "cloud_cover": {
                    "avg": "50.96 %",
                    "max": "100 %",
                    "min": "6 %"
                },
                "dew_point": {
                    "avg": "12.03 °C",
                    "max": "14.06 °C",
                    "min": "9.69 °C"
                },
                "humidity": {
                    "avg": "64.38 %",
                    "max": "95 %",
                    "min": "37 %"
                },
                "precipitation_probability": {
                    "avg": "0.6 %",
                    "max": "5 %",
                    "min": "0 %"
                },
                "rain": {
                    "accumulation_lwe_avg": "0.18 mm",
                    "accumulation_lwe_max": "3.23 mm",
                    "accumulation_lwe_min": "0 mm",
                    "accumulation_avg": "0.15 mm",
                    "accumulation_max": "2.14 mm",
                    "accumulation_min": "0 mm",
                    "accumulation_sum": "3.71 mm",
                    "intensity_avg": "0.18 mm/h",
                    "intensity_max": "3.23 mm/h",
                    "intensity_min": "0 mm/h"
                },
                "temperature": {
                    "apparent_avg": "19.67 °C",
                    "apparent_max": "25.44 °C",
                    "apparent_min": "13 °C",
                    "avg": "19.67 °C",
                    "max": "25.44 °C",
                    "min": "13 °C"
                },
                "uv": {
                    "health_concern_avg": 1,
                    "health_concern_max": 4,
                    "health_concern_min": 0,
                    "index_avg": 2,
                    "index_max": 10,
                    "index_min": 0
                },
                "visibility": {
                    "avg": "13.64 km",
                    "max": "16 km",
                    "min": "8.22 km"
                },
                "wind": {
                    "direction_avg": "53.94 °",
                    "gust_avg": "6.6 m/s",
                    "gust_max": "9.94 m/s",
                    "gust_min": "3.19 m/s",
                    "speed_avg": "3.47 m/s",
                    "speed_max": "5.25 m/s",
                    "speed_min": "0.69 m/s"
                }
                }
            ]
            }
            ```

            Notes
            - Only include suggestions for the days in the provided weather forecast data captured in the forecast_date key
            - Add the justification for the dressing recommendation and activity suggestions based on the weather forecast data in parenthesis after each recommendation
        EOT;

        $forecastEntries = collect([]);
        foreach ($forecasts as $forecast) {
            $forecastEntries->push(<<<EOT
                {
                    "entity": "weather-forecast",
                    "forecast_date": "{$forecast->forecast_date}",
                    "weather_code": "{$forecast->weather_code}",
                    "weather_code_description": "{$forecast->weather_code_description}",
                    "moon_rise_time": "{$forecast->moon_rise_time}",
                    "moon_set_time": "{$forecast->moon_set_time}",
                    "sun_rise_time": "{$forecast->sun_rise_time}",
                    "sun_set_time": "{$forecast->sun_set_time}",
                    "cloud_cover": {
                        "avg": "{$forecast->cloud_cover['avg']}",
                        "max": "{$forecast->cloud_cover['max']}",
                        "min": "{$forecast->cloud_cover['min']}"
                    },
                    "dew_point": {
                        "avg": "{$forecast->dew_point['avg']}",
                        "max": "{$forecast->dew_point['max']}",
                        "min": "{$forecast->dew_point['min']}"
                    },
                    "humidity": {
                        "avg": "{$forecast->humidity['avg']}",
                        "max": "{$forecast->humidity['max']}",
                        "min": "{$forecast->humidity['min']}"
                    },
                    "precipitation_probability": {
                        "avg": "{$forecast->precipitation_probability['avg']}",
                        "max": "{$forecast->precipitation_probability['max']}",
                        "min": "{$forecast->precipitation_probability['min']}"
                    },
                    "rain": {
                        "accumulation_lwe_avg": "{$forecast->rain['accumulation_lwe_avg']}",
                        "accumulation_lwe_max": "{$forecast->rain['accumulation_lwe_max']}",
                        "accumulation_lwe_min": "{$forecast->rain['accumulation_lwe_min']}",
                        "accumulation_avg": "{$forecast->rain['accumulation_avg']}",
                        "accumulation_max": "{$forecast->rain['accumulation_max']}",
                        "accumulation_min": "{$forecast->rain['accumulation_min']}",
                        "accumulation_sum": "{$forecast->rain['accumulation_sum']}",
                        "intensity_avg": "{$forecast->rain['intensity_avg']}",
                        "intensity_max": "{$forecast->rain['intensity_max']}",
                        "intensity_min": "{$forecast->rain['intensity_min']}"
                    },
                    "temperature": {
                        "apparent_avg": "{$forecast->temperature['apparent_avg']}",
                        "apparent_max": "{$forecast->temperature['apparent_max']}",
                        "apparent_min": "{$forecast->temperature['apparent_min']}",
                        "avg": "{$forecast->temperature['avg']}",
                        "max": "{$forecast->temperature['max']}",
                        "min": "{$forecast->temperature['min']}"
                    },
                    "uv": {
                        "health_concern_avg": "{$forecast->uv['health_concern_avg']}",
                        "health_concern_max": "{$forecast->uv['health_concern_max']}",
                        "health_concern_min": "{$forecast->uv['health_concern_min']}",
                        "index_avg": "{$forecast->uv['index_avg']}",
                        "index_max": "{$forecast->uv['index_max']}",
                        "index_min": "{$forecast->uv['index_min']}"
                    },
                    "visibility": {
                        "avg": "{$forecast->visibility['avg']}",
                        "max": "{$forecast->visibility['max']}",
                        "min": "{$forecast->visibility['min']}"
                    },
                    "wind": {
                        "direction_avg": "{$forecast->wind['direction_avg']}",
                        "gust_avg": "{$forecast->wind['gust_avg']}",
                        "gust_max": "{$forecast->wind['gust_max']}",
                        "gust_min": "{$forecast->wind['gust_min']}",
                        "speed_avg": "{$forecast->wind['speed_avg']}",
                        "speed_max": "{$forecast->wind['speed_max']}",
                        "speed_min": "{$forecast->wind['speed_min']}"
                    }
                }
            EOT);
        }

        $userPrompt = <<<EOT
            {
                "weather_forecasts": [
                    {$forecastEntries->join(",\n")}
                ]
            }
        EOT;

        $dailyResults = $this->runPrompt(
            systemPrompt: $systemPrompt,
            userPrompt: $userPrompt
        );

        // Save the daily recommendations based on the weather forecast
        collect($dailyResults['recommendations'])->each(function ($recommendation) {
            WeatherForecast::query()
                ->where('mission_id', $this->mission->id)
                ->whereDate('forecast_date', $recommendation['date'])
                ->update([
                    'dressing_recommendations' => collect($recommendation['dressing'])->join("\n"),
                    'activity_recommendations' => collect($recommendation['activities'])->join("\n"),
                    'weather_recommendations' => $recommendation,
                ]);
        });

        // Save the overall recommendations of the mission factoring in all the days individual recommendations
        $systemPrompt = <<<'EOT'
            You are an AI assistant designed to provide recommendations for a high school-focused gospel missions group. Summarise
            the dressing recommendations and weather-specific activity suggestions for the upcoming mission days based on
            the daily recommendations provided. The mission policy is provided below. Generate a JSON output with the
            following keys:

            The output must be in JSON format with a top-level key called "recommendations," containing a JSON array of objects. Each object in the array represents a day, with the following keys:
            * `temperature_range`: (string, e.g., "13°C to 25.44°C")
            * `precipitation_probability`: (string, e.g., "Low (0-5%)")
            * `dressing`: (JSON array of strings with specific clothing advice)
            * `activities`: (JSON array of strings with specific, weather-related gospel outreach activities)
        EOT;

        $userPrompt = json_encode($dailyResults['recommendations']);

        $summaryResults = $this->runPrompt(
            systemPrompt: $systemPrompt,
            userPrompt: $userPrompt
        );

        Mission::query()
            ->where('id', $mission->id)
            ->update([
                'dressing_recommendations' => collect($summaryResults['recommendations'][0]['dressing'])->join("\n"),
                'activity_recommendations' => collect($summaryResults['recommendations'][0]['activities'])->join("\n"),
                'weather_recommendations' => $summaryResults['recommendations'][0],
            ]);
    }

    private function runPrompt(string $systemPrompt, string $userPrompt): array
    {
        $model = config('prf.app.gemini.model');

        $response = Http::withHeaders([
            'content-type' => 'application/json',
        ])
            ->timeout(60 * 4)
            ->withQueryParameters([
                'key' => config('prf.app.gemini.api_key'),

            ])->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $systemPrompt,
                                ],
                                [
                                    'text' => $userPrompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => config('prf.app.gemini.max_output_tokens'),
                    ],
                ]
            );

        $json = Str::of($response->json()['candidates'][0]['content']['parts'][0]['text'])
            ->replace('```json', '')
            ->replace('```', '');

        sleep(6); // Sleep for 6 seconds to be in the Gemini API usage quota

        return json_decode($json, true);
    }
}
