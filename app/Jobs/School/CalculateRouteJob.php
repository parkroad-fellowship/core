<?php

namespace App\Jobs\School;

use App\Contracts\Services\MapsServiceInterface;
use App\Models\RouteDistance;
use App\Models\School;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
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
    public function handle(MapsServiceInterface $maps): void
    {
        $school = $this->school;

        $origin = [
            'latitude' => (float) config('prf.app.head_office.latitude'),
            'longitude' => (float) config('prf.app.head_office.longitude'),
        ];

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
        $results = $maps->computeRoute(
            origin: $origin,
            destination: [
                'latitude' => (float) $school->latitude,
                'longitude' => (float) $school->longitude,
            ],
        );

        Log::info('Google Maps API response', [$results]);

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
