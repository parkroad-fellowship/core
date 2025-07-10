<?php

namespace App\Jobs\School;

use App\Models\RouteDistance;
use App\Models\School;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalculateRouteJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public School $school,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $school = $this->school;

        // Check if the distance to buyer has already been calculated and stored before
        $routeDistance = RouteDistance::query()
            ->where([
                'origin_latitude' => config('prf.app.head_office.latitude'),
                'origin_longitude' => config('prf.app.head_office.longitude'),
                'destination_latitude' => $school->latitude,
                'destination_longitude' => $school->longitude,
            ])
            ->first();

        // If the distance already exists, update the school's distance and static duration
        if ($routeDistance !== null) {
            School::query()
                ->where('id', $school->id)
                ->update([
                    'distance' => $routeDistance->distance,
                    'static_duration' => $routeDistance->static_duration,
                ]);

            return;
        }

        if (app()->environment('testing')) {
            Log::info('Skipping Google Maps API call in testing environment');
            School::query()
                ->where('id', $school->id)
                ->update([
                    'distance' => '10 km (test)',
                    'static_duration' => '15 mins (test)',
                ]);

            return;
        }

        // Calculate the distance and static duration to the school
        $results = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Goog-Api-Key' => config('prf.app.google_maps.api_key'),
            'X-Goog-FieldMask' => 'routes.localizedValues',
        ])
            ->post(
                'https://routes.googleapis.com/directions/v2:computeRoutes',
                [
                    'origin' => [
                        'location' => [
                            'latLng' => [
                                'latitude' => config('prf.app.head_office.latitude'),
                                'longitude' => config('prf.app.head_office.longitude'),
                            ],
                        ],
                    ],
                    'destination' => [
                        'location' => [
                            'latLng' => [
                                'latitude' => $school->latitude,
                                'longitude' => $school->longitude,
                            ],
                        ],
                    ],
                    'travelMode' => 'DRIVE',
                    'routingPreference' => 'TRAFFIC_AWARE',
                    'computeAlternativeRoutes' => false,
                    'routeModifiers' => [
                        'avoidTolls' => false,
                        'avoidHighways' => false,
                        'avoidFerries' => false,
                    ],
                    'languageCode' => 'en-US',
                    'units' => 'METRIC',
                ],
            );

        Log::info('Google Maps API response', [$results->json()]);

        $results = $results->json();

        if (Arr::has($results, 'routes.0.localizedValues')) {
            $localizedValues = Arr::get($results, 'routes.0.localizedValues');

            $routeDistance = RouteDistance::create([
                'origin_latitude' => config('prf.app.head_office.latitude'),
                'origin_longitude' => config('prf.app.head_office.longitude'),
                'destination_latitude' => $school->latitude,
                'destination_longitude' => $school->longitude,
                'distance' => Arr::get($localizedValues, 'distance.text'),
                'static_duration' => Arr::get($localizedValues, 'staticDuration.text'),
            ]);

            School::query()
                ->where('id', $school->id)
                ->update([
                    'distance' => $routeDistance->distance,
                    'static_duration' => $routeDistance->static_duration,
                ]);
        }
    }
}
