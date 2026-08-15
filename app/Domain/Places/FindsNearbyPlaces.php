<?php

namespace App\Domain\Places;

interface FindsNearbyPlaces
{
    /**
     * @return PlaceResult[]
     */
    public function nearby(float $lat, float $lng, int $radiusMeters): array;
}
