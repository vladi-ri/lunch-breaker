<?php

namespace App\Domain\Places;

use App\Domain\Places\Drivers\GoogleDriver;
use App\Domain\Places\Drivers\OsmDriver;
use Illuminate\Support\Manager;

/**
 * @mixin \App\Domain\Places\FindsNearbyPlaces
 */
class PlacesManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->container['config']->get('services.places.driver', 'osm');
    }

    public function createOsmDriver(): OsmDriver
    {
        return new OsmDriver;
    }

    public function createGoogleDriver(): GoogleDriver
    {
        return new GoogleDriver(
            apiKey: $this->container['config']->get('services.google.places_api_key'),
        );
    }
}
