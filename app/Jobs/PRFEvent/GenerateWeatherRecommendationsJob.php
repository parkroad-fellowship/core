<?php

namespace App\Jobs\PRFEvent;

use App\Enums\PRFMorphType;
use App\Models\Mission;
use App\Models\PRFEvent;
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
        public PRFEvent $prfEvent,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $prfEvent = $this->prfEvent;

        $forecasts = WeatherForecast::query()
            ->where([
                'weather_forecastable_id' => $prfEvent->id,
                'weather_forecastable_type' => PRFMorphType::EVENT->value,
            ])
            ->get();

        if (! $forecasts->count()) {
            Log::error('Weather forecast not found for event', ['prf_event_id' => $prfEvent->id]);

            return;
        }

        /**
         * 1. DAILY RECOMMENDATION PROMPT
         * Using PTCF Framework & Mission Policy Section H
         */
        $systemPrompt = <<<'EOT'
            **PERSONA:**
            You are the Senior Logistics Coordinator and Wardrobe Advisor for Parkroad Fellowship (PRF). You are an expert in preparing interdenominational lay teams for high school-focused gospel events, ensuring they are practically prepared for the weather while remaining constitutionally compliant.

            **CONTEXT & POLICY (Section H):**
            PRF is a lay ministry instructing youth on "holistic living and values." As such, we must model modesty.
            - **General Modesty**: All clothing must be clean and cover both shoulders and knees.
            - **Men**: T-shirts, shirts, long pants, and closed-toe shoes.
            - **Women**: Dresses/skirts (covering knees), blouses (covering shoulders), and closed-toe shoes or dress sandals.
            - **Practicality**: Layering is required. Rain gear must be suggested if there is any significant precipitation probability.
            - **Activities**: Suggestions should leverage "marketplace skills" and focus on gospel outreach.

            **TASK:**
            Analyze the provided JSON weather forecast. Generate a JSON output for dressing and activities.

            **FORMAT (Strict JSON):**
            Return a JSON object with a "recommendations" key containing an array of objects:
            * `date`: (YYYY-MM-DD)
            * `weather_description`: (e.g., "Mostly Clear")
            * `temperature_range`: (e.g., "15°C to 28°C")
            * `precipitation_probability`: (e.g., "Low (5%)")
            * `dressing`: (Array of strings. Include a parenthetical justification referencing Section H and specific weather data).
            * `activities`: (Array of strings. Suggest gospel-related activities tailored to the weather).

            **NOTE:** Every recommendation must be justified in parentheses based on the specific forecast data.
            EOT;

        $forecastEntries = collect([]);
        foreach ($forecasts as $forecast) {
            $forecastEntries->push(json_encode([
                'entity' => 'weather-forecast',
                'forecast_date' => $forecast->forecast_date,
                'weather_code_description' => $forecast->weather_code_description,
                'precipitation_probability' => $forecast->precipitation_probability,
                'temperature' => $forecast->temperature,
                'uv' => $forecast->uv,
                'wind' => $forecast->wind,
                'humidity' => $forecast->humidity,
            ]));
        }

        $userPrompt = '{"weather_forecasts": ['.$forecastEntries->join(',').']}';

        $dailyResults = $this->runPrompt(
            systemPrompt: $systemPrompt,
            userPrompt: $userPrompt
        );

        // Save the daily recommendations
        collect($dailyResults['recommendations'])->each(function ($recommendation) {
            WeatherForecast::query()
                ->where([
                    'weather_forecastable_id' => $this->prfEvent->id,
                    'weather_forecastable_type' => PRFMorphType::EVENT->value,
                ])
                ->whereDate('forecast_date', $recommendation['date'])
                ->update([
                    'dressing_recommendations' => collect($recommendation['dressing'])->join("\n"),
                    'weather_recommendations' => $recommendation,
                ]);
        });

        /**
         * 2. SUMMARY PROMPT
         * Summarizes multiple days into a single event-level logistics guide.
         */
        $summarySystemPrompt = <<<'EOT'
            **PERSONA:** Senior Logistics Coordinator for Parkroad Fellowship.

            **TASK:**
            Summarize the daily weather recommendations into a single high-level guide for the event attendees.

            **REQUIREMENTS:**
            - Consolidate dressing advice into a "standard mission look" for this event.
            - Highlight the primary weather risk (e.g., UV exposure, wind, or rain).
            - Ensure the summary strictly follows Section H (shoulders and knees covered).

            **FORMAT (Strict JSON):**
            Return a JSON object with a "recommendations" key containing an array with one object:
            * `temperature_range`: (Overall range for the event)
            * `precipitation_probability`: (Highest risk noted)
            * `dressing`: (Consolidated array of clothing advice)
            EOT;

        $userPrompt = json_encode($dailyResults['recommendations']);

        $summaryResults = $this->runPrompt(
            systemPrompt: $summarySystemPrompt,
            userPrompt: $userPrompt
        );

        PRFEvent::query()
            ->where('id', $prfEvent->id)
            ->update([
                'dressing_recommendations' => collect($summaryResults['recommendations'][0]['dressing'])->join("\n"),
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
                "https://generativelanguage.googleapis.com/v1beta/{$model}:generateContent",
                [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => 'SYSTEM INSTRUCTION: '.$systemPrompt,
                                ],
                                [
                                    'text' => $userPrompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => config('prf.app.gemini.max_output_tokens'),
                        'response_mime_type' => 'application/json',
                    ],
                ]
            );

        if ($response->failed()) {
            Log::error('Gemini API Error in Event Weather Job', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['recommendations' => []];
        }

        $text = $response->json()['candidates'][0]['content']['parts'][0]['text'];

        $json = Str::of($text)
            ->replace('```json', '')
            ->replace('```', '')
            ->trim();

        sleep(6); // Sleep for 6 seconds to manage API quota

        return json_decode($json, true) ?? ['recommendations' => []];
    }
}
