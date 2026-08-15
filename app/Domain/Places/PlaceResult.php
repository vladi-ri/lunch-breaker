<?php

namespace App\Domain\Places;

readonly class PlaceResult
{
    public function __construct(
        public string $externalId,
        public string $name,
        public float $latitude,
        public float $longitude,
        public ?string $address,
        public ?string $category,
    ) {}
}
