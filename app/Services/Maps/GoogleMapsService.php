<?php

namespace App\Services\Maps;

use App\Contracts\Services\MapsServiceInterface;
use Illuminate\Support\Facades\Http;

class GoogleMapsService implements MapsServiceInterface
{
    public function computeRoute(array $origin, array $destination): array
    {
        $response = Http::withHeaders([
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
                                'latitude' => $origin['latitude'],
                                'longitude' => $origin['longitude'],
                            ],
                        ],
                    ],
                    'destination' => [
                        'location' => [
                            'latLng' => [
                                'latitude' => $destination['latitude'],
                                'longitude' => $destination['longitude'],
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

        return $response->json();
    }
}
