<?php

namespace App\Jobs\Mission;

use App\Enums\PRFMorphType;
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
            ->where([
                'weather_forecastable_id' => $mission->id,
                'weather_forecastable_type' => PRFMorphType::MISSION->value,
            ])
            ->get();

        if (! $forecasts->count()) {
            Log::error('Weather forecast not found for mission', ['mission_id' => $mission->id]);

            return;
        }

        /**
         * 1. DAILY RECOMMENDATION PROMPT
         * Following PTCF Guidelines and Mission Policy Section H
         */
        $systemPrompt = <<<'EOT'
            **PERSONA:**
            You are the Senior Mission Logistics Coordinator and Spiritual Advisor for Parkroad Fellowship (PRF). You specialize in preparing interdenominational lay teams for high school gospel missions, balancing practical safety with spiritual intentionality.

            **CONTEXT & POLICY ALIGNMENT:**
            PRF is a lay ministry that uses "marketplace acquired skills" to instruct youth on "holistic living, values, and career choices." According to our Mission Policy (Section H):
            - All attire must be clean, modest, and practical.
            - "Shoulders and knees must be covered" at all times.
            - Men: T-shirts/shirts, long pants, and closed-toe shoes.
            - Women: Dresses/skirts (below the knee), blouses (covering shoulders), and closed-toe shoes or dress sandals.
            - Practicality: Layering is required for varying weather; rain gear is mandatory if precipitation is forecasted.

            **TASK:**
            Analyze the provided JSON weather data and generate dressing and activity recommendations for each day.

            **OUTPUT FORMAT (Strict JSON):**
            Return a JSON object with a "recommendations" key containing an array of objects:
            * `date`: (YYYY-MM-DD)
            * `weather_description`: (e.g., "Partly Cloudy")
            * `temperature_range`: (e.g., "14°C to 26°C")
            * `precipitation_probability`: (e.g., "High (70%)")
            * `dressing`: (Array of strings. Include a justification in parentheses referencing Section H and the weather).
            * `activities`: (Array of strings. Focus on "gospel-related opportunities" and "marketplace skills").

            **REQUIREMENT:** Every recommendation MUST include a justification in parentheses.
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

        // Save daily recommendations
        collect($dailyResults['recommendations'])->each(function ($recommendation) {
            WeatherForecast::query()
                ->where([
                    'weather_forecastable_id' => $this->mission->id,
                    'weather_forecastable_type' => PRFMorphType::MISSION->value,
                ])
                ->whereDate('forecast_date', $recommendation['date'])
                ->update([
                    'dressing_recommendations' => collect($recommendation['dressing'])->join("\n"),
                    'activity_recommendations' => collect($recommendation['activities'])->join("\n"),
                    'weather_recommendations' => $recommendation,
                ]);
        });

        /**
         * 2. MISSION SUMMARY PROMPT
         * Summarizes all days into a single executive logistics overview
         */
        $summarySystemPrompt = <<<'EOT'
            **PERSONA:** You are the Senior Mission Logistics Coordinator for Parkroad Fellowship.

            **TASK:**
            Summarize the daily weather recommendations into a single mission-level overview for the Mission Leader.

            **REQUIREMENTS:**
            - Consolidate dressing advice into a "Mission-Ready" standard based on Section H.
            - Highlight the primary weather risk (e.g., extreme heat or heavy rain).
            - Suggest a high-level gospel outreach strategy that fits the overall climate.

            **FORMAT:**
            Return a JSON object with a "recommendations" key containing an array with a single summary object:
            * `temperature_range`: (Overall range)
            * `precipitation_probability`: (Highest risk noted)
            * `dressing`: (Consolidated array focusing on layering and modesty)
            * `activities`: (High-level activity strategy)
            EOT;

        $summaryResults = $this->runPrompt(
            systemPrompt: $summarySystemPrompt,
            userPrompt: json_encode($dailyResults['recommendations'])
        );

        // Update the main Mission record with the summary
        $mission->update([
            'dressing_recommendations' => collect($summaryResults['recommendations'][0]['dressing'])->join("\n"),
            'activity_recommendations' => collect($summaryResults['recommendations'][0]['activities'])->join("\n"),
            'weather_recommendations' => $summaryResults['recommendations'][0],
        ]);
    }

    private function runPrompt(string $systemPrompt, string $userPrompt): array
    {
        $model = config('prf.app.gemini.model');

        $response = Http::withHeaders(['content-type' => 'application/json'])
            ->timeout(60 * 4)
            ->withQueryParameters(['key' => config('prf.app.gemini.api_key')])
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/{$model}:generateContent",
                [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => 'SYSTEM INSTRUCTION: '.$systemPrompt],
                                ['text' => $userPrompt],
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
            Log::error('Gemini API Error in Weather Job', ['body' => $response->body()]);

            return ['recommendations' => []];
        }

        $text = $response->json()['candidates'][0]['content']['parts'][0]['text'];

        // Clean markdown if present
        $json = Str::of($text)
            ->replace('```json', '')
            ->replace('```', '')
            ->trim();

        sleep(2); // Rate limit breathing room

        return json_decode($json, true) ?? ['recommendations' => []];
    }
}
