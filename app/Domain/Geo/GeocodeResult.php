<?php

namespace App\Domain\Geo;

readonly class GeocodeResult
{
    public function __construct(
        public float $latitude,
        public float $longitude,
        public string $formattedAddress,
    ) {}
}
