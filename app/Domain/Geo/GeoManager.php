<?php

namespace App\Domain\Geo;

use App\Domain\Geo\Drivers\GoogleDriver;
use App\Domain\Geo\Drivers\OsmDriver;
use Illuminate\Support\Manager;

/**
 * @mixin \App\Domain\Geo\GeocodesAddresses
 * @mixin \App\Domain\Geo\CalculatesWalkingDistance
 */
class GeoManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->container['config']->get('services.geo.driver', 'osm');
    }

    public function createOsmDriver(): OsmDriver
    {
        return new OsmDriver(
            osrmBaseUrl: $this->container['config']->get('services.osrm.base_url'),
        );
    }

    public function createGoogleDriver(): GoogleDriver
    {
        return new GoogleDriver(
            apiKey: $this->container['config']->get('services.google.places_api_key'),
        );
    }
}
