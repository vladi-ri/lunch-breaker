<?php

namespace App\Domain\Geo;

interface CalculatesWalkingDistance
{
    public function walkingDistance(float $fromLat, float $fromLng, float $toLat, float $toLng): ?WalkingResult;
}
