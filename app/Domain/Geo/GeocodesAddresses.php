<?php

namespace App\Domain\Geo;

interface GeocodesAddresses
{
    public function geocode(string $address): ?GeocodeResult;
}
