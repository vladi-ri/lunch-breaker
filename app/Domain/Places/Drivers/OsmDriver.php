<?php

namespace App\Domain\Places\Drivers;

use App\Domain\Places\FindsNearbyPlaces;
use App\Domain\Places\PlaceResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OsmDriver implements FindsNearbyPlaces
{
    // amenity=* covers places you sit down and order (incl. workplace/school canteens).
    protected const AMENITIES = ['restaurant', 'cafe', 'fast_food', 'food_court', 'canteen'];

    // Bakeries/butchers/supermarkets are a shop=* in OSM, not an amenity —
    // amenity=bakery isn't a real tag, so it never matched anything before this
    // was split out. German butchers (Fleischerei) commonly sell hot snacks/lunch
    // items too, same as bakeries, and supermarkets often have a deli/salad bar.
    protected const SHOPS = ['bakery', 'butcher', 'supermarket'];

    public function __construct(
        protected string $overpassUrl = 'https://overpass-api.de/api/interpreter',
        protected string $userAgent = 'LunchBreakerApp/1.0 (+http://lunch-breaker.test)',
    ) {}

    public function nearby(float $lat, float $lng, int $radiusMeters): array
    {
        $amenityFilter = implode('|', self::AMENITIES);
        $shopFilter = implode('|', self::SHOPS);

        $query = <<<OVERPASS
            [out:json][timeout:25];
            (
              node["amenity"~"^({$amenityFilter})$"](around:{$radiusMeters},{$lat},{$lng});
              node["shop"~"^({$shopFilter})$"](around:{$radiusMeters},{$lat},{$lng});
            );
            out body;
            OVERPASS;

        $response = Http::withHeaders(['User-Agent' => $this->userAgent])
            ->asForm()
            ->post($this->overpassUrl, ['data' => $query]);

        if (! $response->ok()) {
            Log::warning('Overpass nearby search failed', ['status' => $response->status()]);

            return [];
        }

        $elements = $response->json('elements', []);

        return collect($elements)
            ->filter(fn (array $element) => ! empty($element['tags']['name']))
            ->unique('id')
            ->map(function (array $element) {
                $tags = $element['tags'];

                $address = collect([
                    $tags['addr:street'] ?? null,
                    $tags['addr:housenumber'] ?? null,
                ])->filter()->implode(' ');

                return new PlaceResult(
                    externalId: (string) $element['id'],
                    name: $tags['name'],
                    latitude: (float) $element['lat'],
                    longitude: (float) $element['lon'],
                    address: $address !== '' ? $address : null,
                    category: $tags['amenity'] ?? $tags['shop'] ?? null,
                );
            })
            ->values()
            ->all();
    }
}
