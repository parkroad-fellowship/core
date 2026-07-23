<?php

namespace App\Contracts\Services;

interface MapsServiceInterface
{
    /**
     * Compute a route between two points.
     *
     * @param  array{latitude: float, longitude: float}  $origin
     * @param  array{latitude: float, longitude: float}  $destination
     * @return array{routes?: array}
     */
    public function computeRoute(array $origin, array $destination): array;
}
